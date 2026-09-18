<?php

namespace App\Services\Antrian;

use App\Jobs\SendQueueNotification;
use App\Models\Khanza\RegPeriksa;
use App\Models\QueueTicket;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        $ticket->forceFill(['notified_called_at' => now()])->save();
        SendQueueNotification::dispatch($ticket->id, 'called');

        $this->recalculateNearNotifications($kdPoli, $tanggal);

        return $ticket;
    }

    public function markDone(QueueTicket $ticket): void
    {
        $ticket->update(['status' => 'done', 'done_at' => now()]);

        $this->recalculateNearNotifications($ticket->kd_poli, $ticket->tanggal);
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
    private function recalculateNearNotifications(string $kdPoli, Carbon $tanggal): void
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
