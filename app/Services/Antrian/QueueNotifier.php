<?php

namespace App\Services\Antrian;

use App\Models\PatientAccount;
use App\Models\PatientNotificationLog;
use App\Models\QueueTicket;
use App\Repositories\Khanza\PoliklinikRepository;
use Gowa\Laravel\Facades\Gowa;
use Gowa\Sdk\Exceptions\GowaException;

/**
 * Sends the three queue WhatsApp notifications (issued / near / called),
 * following the same Gowa usage as PatientOtpService::send(): the fluent
 * facade plus a manual PatientNotificationLog row, rather than the
 * Notification/Channel system this app doesn't otherwise use.
 */
class QueueNotifier
{
    public function __construct(
        private readonly QueueTicketService $queueTicketService,
        private readonly PoliklinikRepository $poliklinikRepository,
    ) {}

    public function notifyIssued(QueueTicket $ticket): void
    {
        $poli = $this->poliName($ticket->kd_poli);

        $this->send($ticket, 'queue_issued', "Nomor antrean Anda di {$poli} adalah {$ticket->queue_number}. Kami akan mengabari Anda saat giliran Anda mendekat.");
    }

    public function notifyNear(QueueTicket $ticket): void
    {
        $poli = $this->poliName($ticket->kd_poli);
        $position = $this->queueTicketService->positionAhead($ticket);

        $this->send($ticket, 'queue_near', "Antrean Anda nomor {$ticket->queue_number} di {$poli} sebentar lagi ({$position} pasien lagi sebelum Anda). Mohon bersiap menuju ruang tunggu.");
    }

    public function notifyCalled(QueueTicket $ticket): void
    {
        $poli = $this->poliName($ticket->kd_poli);

        $this->send($ticket, 'queue_called', "Giliran Anda! Nomor antrean {$ticket->queue_number} dipanggil di {$poli}. Silakan menuju ruang periksa.");
    }

    private function send(QueueTicket $ticket, string $type, string $message): void
    {
        $account = PatientAccount::query()->where('no_rkm_medis', $ticket->no_rkm_medis)->first();

        if (! $account || ! $account->isWaVerified() || ! $account->notify_queue) {
            return;
        }

        try {
            $sent = Gowa::to($account->wa_number)->text($message)->send();

            PatientNotificationLog::query()->create([
                'no_rkm_medis' => $ticket->no_rkm_medis,
                'wa_number' => $account->wa_number,
                'type' => $type,
                'message' => $message,
                'status' => 'sent',
                'gowa_message_id' => $sent->providerMessageId,
                'sent_at' => now(),
            ]);
        } catch (GowaException $exception) {
            PatientNotificationLog::query()->create([
                'no_rkm_medis' => $ticket->no_rkm_medis,
                'wa_number' => $account->wa_number,
                'type' => $type,
                'message' => $message,
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function poliName(string $kdPoli): string
    {
        return $this->poliklinikRepository->find($kdPoli)?->nm_poli ?? $kdPoli;
    }
}
