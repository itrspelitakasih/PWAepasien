<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\QueueTicket;
use App\Repositories\Khanza\RegPeriksaRepository;
use App\Services\Antrian\QueueTicketService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SyncKhanzaQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'antrian:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll Khanza reg_periksa for new outpatient registrations, issue queue tickets, and keep today\'s tickets in step with Khanza (cancel tickets whose visit is cancelled or gone, restore ones whose visit is back)';

    private const CURSOR_CACHE_KEY = 'antrian:sync:cursor';

    public function handle(RegPeriksaRepository $regPeriksaRepository, QueueTicketService $queueTicketService): int
    {
        // No cursor cached yet means this poller has never run before —
        // start from today rather than walking Khanza's entire `reg_periksa`
        // history (which can be hundreds of thousands of rows) 200 at a time.
        $cursor = (string) Cache::get(self::CURSOR_CACHE_KEY, Carbon::today()->format('Y/m/d').'/000000');

        $newRegistrations = $regPeriksaRepository->newSince($cursor);

        // A registration can already have a ticket when the cursor was lost
        // (cache cleared) or the connection was switched to another database;
        // re-issuing would violate the unique `no_rawat` and wedge the poller.
        $alreadyTicketed = QueueTicket::query()
            ->whereIn('no_rawat', $newRegistrations->pluck('no_rawat'))
            ->pluck('no_rawat')
            ->flip();

        $issuedCount = 0;

        foreach ($newRegistrations as $regPeriksa) {
            // Already examined or cancelled by the time the poller sees it:
            // no queue to join, so no ticket (and no "ticket issued" message).
            $closed = in_array($regPeriksa->stts, ['Sudah', 'Batal'], true);

            if (! $closed && ! $alreadyTicketed->has($regPeriksa->no_rawat)) {
                $queueTicketService->issueTicket($regPeriksa);
                $issuedCount++;
            }

            $cursor = $regPeriksa->no_rawat;
        }

        if ($newRegistrations->isNotEmpty()) {
            Cache::forever(self::CURSOR_CACHE_KEY, $cursor);
        }

        [$cancelledCount, $restoredCount, $doneCount] = $this->reconcileTodaysTickets($regPeriksaRepository, $queueTicketService);

        $this->info(sprintf(
            'Issued %d new ticket(s), cancelled %d ticket(s), restored %d ticket(s), finished %d ticket(s).',
            $issuedCount,
            $cancelledCount,
            $restoredCount,
            $doneCount,
        ));

        return self::SUCCESS;
    }

    /**
     * Keeps today's tickets in step with Khanza: a ticket exists only while
     * its visit exists there. A visit that is missing (deleted, or the
     * connection now points at another database) or marked `Batal` can never
     * be served, so its ticket is cancelled; a cancelled ticket whose visit
     * is present and not `Batal` is brought back. A visit already examined
     * (`Sudah`) has its ticket marked done.
     *
     * @return array{int, int, int} [cancelled, restored, done]
     */
    private function reconcileTodaysTickets(RegPeriksaRepository $regPeriksaRepository, QueueTicketService $queueTicketService): array
    {
        $tickets = QueueTicket::query()
            ->whereIn('status', ['waiting', 'called', 'cancelled'])
            ->whereDate('tanggal', today())
            ->get();

        if ($tickets->isEmpty()) {
            return [0, 0, 0];
        }

        $statuses = $regPeriksaRepository->statusFor($tickets->pluck('no_rawat')->all());
        $toCancel = [];
        $restored = 0;
        $done = 0;

        foreach ($tickets as $ticket) {
            $stts = $statuses[$ticket->no_rawat] ?? null;

            if ($stts === 'Sudah') {
                $queueTicketService->markDoneQuietly($ticket);
                $done++;
            } elseif ($stts !== null && $stts !== 'Batal') {
                if ($ticket->status === 'cancelled') {
                    $queueTicketService->restore($ticket);
                    $restored++;
                }
            } elseif ($ticket->status !== 'cancelled') {
                $toCancel[] = $ticket;
            }
        }

        // Status changes above are silent; notify "nearly up" once, against
        // the final state, so nobody who was just closed gets pinged.
        $tickets
            ->unique('kd_poli')
            ->each(fn (QueueTicket $ticket) => $queueTicketService->recalculateNearNotifications($ticket->kd_poli, $ticket->tanggal));

        foreach ($toCancel as $ticket) {
            $queueTicketService->cancel($ticket);
        }

        return [count($toCancel), $restored, $done];
    }
}
