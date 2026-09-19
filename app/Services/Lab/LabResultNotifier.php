<?php

namespace App\Services\Lab;

use App\Models\LabResultNotification;
use App\Models\PatientAccount;
use App\Models\PatientNotificationLog;
use Gowa\Laravel\Facades\Gowa;
use Gowa\Sdk\Exceptions\GowaException;

/**
 * Sends the "hasil laboratorium siap" WhatsApp message, following the same
 * Gowa + PatientNotificationLog pattern as QueueNotifier.
 */
class LabResultNotifier
{
    public const MESSAGE = 'Hasil laboratorium Anda sudah selesai. Silakan hubungi bagian laboratorium atau petugas pendaftaran untuk mengambil hasilnya.';

    public function notify(LabResultNotification $notification): void
    {
        $account = PatientAccount::query()->where('no_rkm_medis', $notification->no_rkm_medis)->first();

        if (! $account || ! $account->isWaVerified() || ! $account->notify_lab_result) {
            return;
        }

        try {
            $sent = Gowa::to($account->wa_number)->text(self::MESSAGE)->send();

            PatientNotificationLog::query()->create([
                'no_rkm_medis' => $notification->no_rkm_medis,
                'wa_number' => $account->wa_number,
                'type' => 'lab_result',
                'message' => self::MESSAGE,
                'status' => 'sent',
                'gowa_message_id' => $sent->providerMessageId,
                'sent_at' => now(),
            ]);
        } catch (GowaException $exception) {
            PatientNotificationLog::query()->create([
                'no_rkm_medis' => $notification->no_rkm_medis,
                'wa_number' => $account->wa_number,
                'type' => 'lab_result',
                'message' => self::MESSAGE,
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
