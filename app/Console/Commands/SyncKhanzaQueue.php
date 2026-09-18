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
    protected $description = 'Poll Khanza reg_periksa for new outpatient registrations, issue queue tickets, and cancel tickets for visits cancelled in Khanza';

    private const CURSOR_CACHE_KEY = 'antrian:sync:cursor';

    public function handle(RegPeriksaRepository $regPeriksaRepository, QueueTicketService $queueTicketService): int
    {
        // No cursor cached yet means this poller has never run before —
        // start from today rather than walking Khanza's entire `reg_periksa`
        // history (which can be hundreds of thousands of rows) 200 at a time.
        $cursor = (string) Cache::get(self::CURSOR_CACHE_KEY, Carbon::today()->format('Y/m/d').'/000000');

        $newRegistrations = $regPeriksaRepository->newSince($cursor);

        foreach ($newRegistrations as $regPeriksa) {
            $queueTicketService->issueTicket($regPeriksa);
            $cursor = $regPeriksa->no_rawat;
        }

        if ($newRegistrations->isNotEmpty()) {
            Cache::forever(self::CURSOR_CACHE_KEY, $cursor);
        }

        $cancelledCount = $this->cancelWithdrawnTickets($regPeriksaRepository, $queueTicketService);

        $this->info(sprintf(
            'Issued %d new ticket(s), cancelled %d ticket(s).',
            $newRegistrations->count(),
            $cancelledCount,
        ));

        return self::SUCCESS;
    }

    private function cancelWithdrawnTickets(RegPeriksaRepository $regPeriksaRepository, QueueTicketService $queueTicketService): int
    {
        $activeTickets = QueueTicket::query()
            ->whereIn('status', ['waiting', 'called'])
            ->whereDate('tanggal', today())
            ->get();

        if ($activeTickets->isEmpty()) {
            return 0;
        }

        $statuses = $regPeriksaRepository->statusFor($activeTickets->pluck('no_rawat')->all());
        $cancelled = 0;

        foreach ($activeTickets as $ticket) {
            if (($statuses[$ticket->no_rawat] ?? null) === 'Batal') {
                $queueTicketService->cancel($ticket);
                $cancelled++;
            }
        }

        return $cancelled;
    }
}
