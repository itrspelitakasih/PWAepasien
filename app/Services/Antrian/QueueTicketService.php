<?php

namespace App\Services\Antrian;

use App\Jobs\SendQueueNotification;
use App\Models\Khanza\RegPeriksa;
use App\Models\QueueTicket;
use App\Repositories\Khanza\RegPeriksaRepository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Owns all queue-state transitions for `queue_tickets` (this app's own
 * source of truth — see the note on App\Models\QueueTicket for why it's
 * not backed by Khanza's antripoli/antriadmisi). Every transition that
 * should notify a patient dispatches SendQueueNotification so sending
 * never blocks the poller or a staff click.
 */
class QueueTicketService
{
    /**
     * Seconds a patient's tickets are trusted before Khanza is asked again —
     * the board polls every 10s, and one indexed lookup per poll per patient
     * is more than the data needs.
     */
    private const PATIENT_SYNC_TTL = 8;

    public function __construct(private readonly RegPeriksaRepository $regPeriksaRepository) {}

    /**
     * Brings one patient's tickets for today in step with their Khanza
     * registrations, so the portal reflects a fresh registration (or a
     * cancelled/finished one) immediately rather than after the next
     * `antrian:sync` tick. A registration means a ticket; no registration
     * means no ticket. Never throws: if Khanza is unreachable the tickets
     * already stored are simply shown as they are.
     */
    public function syncPatientToday(string $noRkmMedis): void
    {
        if (! Cache::add('antrian:patient-sync:'.$noRkmMedis, true, self::PATIENT_SYNC_TTL)) {
            return;
        }

        try {
            $visits = $this->regPeriksaRepository->ralanForPatientOn($noRkmMedis, Carbon::today()->toDateString());
        } catch (Throwable $exception) {
            report($exception);

            return;
        }

        $tickets = QueueTicket::query()
            ->where('no_rkm_medis', $noRkmMedis)
            ->whereDate('tanggal', Carbon::today())
            ->get()
            ->keyBy('no_rawat');

        foreach ($visits as $visit) {
            $ticket = $tickets->pull($visit->no_rawat);
            $stts = $visit->stts;

            if ($ticket === null) {
                if (! in_array($stts, ['Sudah', 'Batal'], true)) {
                    try {
                        $this->issueTicket($visit);
                    } catch (QueryException $exception) {
                        // The poller issued this visit's ticket a moment ago.
                        if (! str_contains($exception->getMessage(), 'Duplicate entry')
                            && ! str_contains($exception->getMessage(), 'UNIQUE constraint failed')) {
                            throw $exception;
                        }
                    }
                }

                continue;
            }

            if ($stts === 'Sudah') {
                if (in_array($ticket->status, ['waiting', 'called', 'cancelled'], true)) {
                    $this->markDone($ticket);
                }
            } elseif ($stts === 'Batal') {
                if (in_array($ticket->status, ['waiting', 'called'], true)) {
                    $this->cancel($ticket);
                }
            } else {
                $this->restore($ticket);
            }
        }

        // Tickets whose visit is no longer in Khanza at all.
        foreach ($tickets as $ticket) {
            if (in_array($ticket->status, ['waiting', 'called'], true)) {
                $this->cancel($ticket);
            }
        }
    }

    /**
     * Create the next queue ticket for a visit, appending it to the end
     * of that poli's line for the day.
     */
    public function issueTicket(RegPeriksa $regPeriksa): QueueTicket
    {
        $tanggal = $regPeriksa->tgl_registrasi;

        $ticket = $this->withRetry(function () use ($regPeriksa, $tanggal): QueueTicket {
            return DB::transaction(function () use ($regPeriksa, $tanggal): QueueTicket {
                $nextNumber = (int) QueueTicket::query()
                    ->where('kd_poli', $regPeriksa->kd_poli)
                    ->whereDate('tanggal', $tanggal)
                    ->lockForUpdate()
                    ->max('queue_number');

                return QueueTicket::query()->create([
                    'no_rawat' => $regPeriksa->no_rawat,
                    'no_rkm_medis' => $regPeriksa->no_rkm_medis,
                    'kd_poli' => $regPeriksa->kd_poli,
                    'kd_dokter' => $regPeriksa->kd_dokter,
                    'tanggal' => $tanggal,
                    'queue_number' => $nextNumber + 1,
                    'status' => 'waiting',
                ]);
            });
        });

        $ticket->forceFill(['notified_issued_at' => now()])->save();
        SendQueueNotification::dispatch($ticket->id, 'issued');

        $this->recalculateNearNotifications($ticket->kd_poli, $tanggal);

        return $ticket;
    }

    /**
     * Call the oldest waiting ticket for a poli/date. Locks against
     * another staff member calling the same ticket at the same moment.
     */
    public function callNext(string $kdPoli, Carbon $tanggal): ?QueueTicket
    {
        $ticket = DB::transaction(function () use ($kdPoli, $tanggal): ?QueueTicket {
            $ticket = QueueTicket::query()
                ->where('kd_poli', $kdPoli)
                ->whereDate('tanggal', $tanggal)
                ->where('status', 'waiting')
                ->orderBy('queue_number')
                ->lockForUpdate()
                ->first();

            $ticket?->update(['status' => 'called', 'called_at' => now()]);

            return $ticket;
        });

        if (! $ticket) {
            return null;
        }

        $this->notifyCalled($ticket);

        return $ticket;
    }

    /**
     * Call one specific ticket out of FIFO order — used when Khanza itself
     * already identifies which visit to call (the cashier's "Masuk Poli"
     * action), rather than this app picking the oldest waiting ticket for
     * the poli. A no-op if the ticket isn't (still) waiting, so a duplicate
     * call from Khanza — e.g. the button clicked twice — never re-notifies.
     */
    public function callTicket(QueueTicket $ticket): bool
    {
        $called = DB::transaction(function () use ($ticket): bool {
            $locked = QueueTicket::query()->whereKey($ticket->id)->lockForUpdate()->first();

            if (! $locked || $locked->status !== 'waiting') {
                return false;
            }

            $locked->update(['status' => 'called', 'called_at' => now()]);

            return true;
        });

        if (! $called) {
            return false;
        }

        $this->notifyCalled($ticket->fresh());

        return true;
    }

    private function notifyCalled(QueueTicket $ticket): void
    {
        $ticket->forceFill(['notified_called_at' => now()])->save();
        SendQueueNotification::dispatch($ticket->id, 'called');

        $this->recalculateNearNotifications($ticket->kd_poli, $ticket->tanggal);
    }

    public function markDone(QueueTicket $ticket): void
    {
        $this->markDoneQuietly($ticket);

        $this->recalculateNearNotifications($ticket->kd_poli, $ticket->tanggal);
    }

    /**
     * Like markDone() but leaves the "near" notifications to the caller, so a
     * batch of tickets can be closed first and recalculated once afterwards
     * (otherwise a patient about to be closed in the same batch could be told
     * they are nearly up).
     */
    public function markDoneQuietly(QueueTicket $ticket): void
    {
        $ticket->update(['status' => 'done', 'done_at' => now()]);
    }

    public function skip(QueueTicket $ticket): void
    {
        $ticket->update(['status' => 'skipped']);

        $this->recalculateNearNotifications($ticket->kd_poli, $ticket->tanggal);
    }

    public function cancel(QueueTicket $ticket): void
    {
        $ticket->update(['status' => 'cancelled']);

        $this->recalculateNearNotifications($ticket->kd_poli, $ticket->tanggal);
    }

    /**
     * Bring a cancelled ticket back (its visit turned out to exist in
     * Khanza after all). Deliberately sends no notifications — the patient
     * has already been told about this ticket once.
     */
    public function restore(QueueTicket $ticket): void
    {
        if ($ticket->status !== 'cancelled') {
            return;
        }

        $ticket->update(['status' => $ticket->called_at ? 'called' : 'waiting']);
    }

    /**
     * How many other patients are still waiting ahead of this ticket.
     */
    public function positionAhead(QueueTicket $ticket): int
    {
        return QueueTicket::query()
            ->where('kd_poli', $ticket->kd_poli)
            ->whereDate('tanggal', $ticket->tanggal)
            ->where('status', 'waiting')
            ->where('queue_number', '<', $ticket->queue_number)
            ->count();
    }

    public function etaMinutes(QueueTicket $ticket): int
    {
        return $this->positionAhead($ticket) * (int) config('antrian.avg_service_minutes');
    }

    /**
     * Notify any waiting ticket that has just become exactly
     * `near_threshold` patients away from its turn.
     */
    public function recalculateNearNotifications(string $kdPoli, Carbon $tanggal): void
    {
        $threshold = (int) config('antrian.near_threshold');

        QueueTicket::query()
            ->where('kd_poli', $kdPoli)
            ->whereDate('tanggal', $tanggal)
            ->where('status', 'waiting')
            ->orderBy('queue_number')
            ->get()
            ->each(function (QueueTicket $ticket, int $index) use ($threshold): void {
                if ($index !== $threshold || $ticket->notified_near_at !== null) {
                    return;
                }

                $ticket->forceFill(['notified_near_at' => now()])->save();
                SendQueueNotification::dispatch($ticket->id, 'near');
            });
    }

    /**
     * Retries once on a unique-constraint collision (two processes
     * racing for the same queue_number) before giving up.
     */
    private function withRetry(callable $callback): QueueTicket
    {
        try {
            return $callback();
        } catch (QueryException $exception) {
            if (! str_contains($exception->getMessage(), 'Duplicate entry')) {
                throw $exception;
            }

            return $callback();
        }
    }
}
