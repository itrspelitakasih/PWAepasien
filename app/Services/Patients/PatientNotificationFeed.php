<?php

namespace App\Services\Patients;

use App\Models\LabResultNotification;
use App\Models\PatientAccount;
use App\Models\QueueTicket;
use App\Repositories\Khanza\PoliklinikRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The in-app notification list behind the bell icon. Derived from the
 * queue ticket's own notified_* timestamps and the finished lab results
 * recorded by `lab:sync` rather than the WhatsApp log, so it still fills in
 * for patients without a verified WA number.
 */
class PatientNotificationFeed
{
    private const DAYS = 14;

    private const LIMIT = 30;

    public function __construct(
        private readonly PoliklinikRepository $poliklinikRepository,
    ) {}

    /**
     * @return Collection<int, array{type: string, title: string, message: string, at: Carbon}>
     */
    public function for(PatientAccount $account): Collection
    {
        $tickets = $this->recentTickets($account);
        $polis = $this->poliklinikRepository->findMany($tickets->pluck('kd_poli')->unique()->all());

        $labEvents = $this->recentLabResults($account)->map(fn (LabResultNotification $result): array => [
            'type' => 'lab',
            'title' => 'Hasil laboratorium siap',
            'message' => 'Hasil laboratorium Anda sudah selesai. Silakan hubungi bagian laboratorium atau petugas pendaftaran untuk mengambil hasilnya.',
            'at' => $result->resulted_at,
        ]);

        return $tickets
            ->flatMap(function (QueueTicket $ticket) use ($polis): array {
                $poli = $polis->get($ticket->kd_poli)?->nm_poli ?? $ticket->kd_poli;
                $events = [];

                if ($ticket->notified_issued_at) {
                    $events[] = [
                        'type' => 'issued',
                        'title' => 'Nomor antrean terbit',
                        'message' => "Nomor antrean Anda di {$poli} adalah {$ticket->queue_number}.",
                        'at' => $ticket->notified_issued_at,
                    ];
                }

                if ($ticket->notified_near_at) {
                    $events[] = [
                        'type' => 'near',
                        'title' => 'Giliran hampir tiba',
                        'message' => "Antrean {$ticket->queue_number} di {$poli} sebentar lagi. Mohon bersiap menuju ruang tunggu.",
                        'at' => $ticket->notified_near_at,
                    ];
                }

                if ($ticket->notified_called_at) {
                    $events[] = [
                        'type' => 'called',
                        'title' => 'Giliran Anda!',
                        'message' => "Nomor antrean {$ticket->queue_number} dipanggil di {$poli}. Silakan menuju ruang periksa.",
                        'at' => $ticket->notified_called_at,
                    ];
                }

                return $events;
            })
            ->concat($labEvents)
            ->sortByDesc('at')
            ->take(self::LIMIT)
            ->values();
    }

    public function unreadCount(PatientAccount $account): int
    {
        $readAt = $account->notifications_read_at;

        $unreadTickets = $this->recentTickets($account)
            ->sum(fn (QueueTicket $ticket): int => collect([
                $ticket->notified_issued_at,
                $ticket->notified_near_at,
                $ticket->notified_called_at,
            ])->filter(fn (?Carbon $at): bool => $at !== null && ($readAt === null || $at->gt($readAt)))->count());

        $unreadLabResults = $this->recentLabResults($account)
            ->filter(fn (LabResultNotification $result): bool => $readAt === null || $result->resulted_at->gt($readAt))
            ->count();

        return $unreadTickets + $unreadLabResults;
    }

    public function markAllRead(PatientAccount $account): void
    {
        $account->forceFill(['notifications_read_at' => now()])->save();
    }

    /**
     * @return Collection<int, QueueTicket>
     */
    private function recentTickets(PatientAccount $account): Collection
    {
        return QueueTicket::query()
            ->where('no_rkm_medis', $account->no_rkm_medis)
            ->where('tanggal', '>=', Carbon::today()->subDays(self::DAYS))
            ->get();
    }

    /**
     * @return Collection<int, LabResultNotification>
     */
    private function recentLabResults(PatientAccount $account): Collection
    {
        return LabResultNotification::query()
            ->where('no_rkm_medis', $account->no_rkm_medis)
            ->where('resulted_at', '>=', Carbon::today()->subDays(self::DAYS))
            ->get();
    }
}
