<?php

namespace App\Services\Patients;

use App\Models\PatientAccount;
use App\Models\PatientNotificationLog;
use App\Models\PatientOtp;
use App\Repositories\Khanza\PasienRepository;
use Gowa\Laravel\Facades\Gowa;
use Gowa\Sdk\Exceptions\GowaException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Issues and verifies WhatsApp one-time codes for the patient portal, via
 * the Gowa channel already configured for this application.
 */
class PatientOtpService
{
    private const CODE_LENGTH = 6;

    private const EXPIRES_AFTER_MINUTES = 5;

    private const RESEND_THROTTLE_SECONDS = 60;

    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly PasienRepository $pasienRepository,
    ) {}

    /**
     * Send an OTP to the WhatsApp number already on file for this patient
     * (their verified patient_accounts number, falling back to Khanza's
     * `pasien.no_tlp`). Used to log in.
     */
    public function requestLoginOtp(string $noRkmMedis): PatientOtp
    {
        return $this->issue($noRkmMedis, $this->resolveKnownWaNumber($noRkmMedis), 'login');
    }

    /**
     * Send an OTP to a patient-supplied WhatsApp number, to prove they own
     * it before it replaces the number on file.
     */
    public function requestNumberVerificationOtp(string $noRkmMedis, string $waNumber): PatientOtp
    {
        $normalized = self::toWhatsAppId($waNumber);

        if ($normalized === null) {
            throw ValidationException::withMessages([
                'wa_number' => 'Nomor WhatsApp tidak valid.',
            ]);
        }

        return $this->issue($noRkmMedis, $normalized, 'link_wa');
    }

    public function verify(string $noRkmMedis, string $code, string $purpose = 'login'): bool
    {
        $otp = PatientOtp::query()
            ->where('no_rkm_medis', $noRkmMedis)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->isExpired() || $otp->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $otp->code)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->forceFill(['consumed_at' => now()])->save();

        return true;
    }

    /**
     * The WhatsApp number the last consumed OTP for this purpose was sent
     * to, used after verify() succeeds to know which number to persist.
     */
    public function lastVerifiedWaNumber(string $noRkmMedis, string $purpose = 'login'): ?string
    {
        return PatientOtp::query()
            ->where('no_rkm_medis', $noRkmMedis)
            ->where('purpose', $purpose)
            ->whereNotNull('consumed_at')
            ->latest('id')
            ->value('wa_number');
    }

    private function issue(string $noRkmMedis, string $waNumber, string $purpose): PatientOtp
    {
        $throttled = PatientOtp::query()
            ->where('no_rkm_medis', $noRkmMedis)
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->where('created_at', '>=', now()->subSeconds(self::RESEND_THROTTLE_SECONDS))
            ->exists();

        if ($throttled) {
            throw ValidationException::withMessages([
                'no_rkm_medis' => 'Kode OTP baru saja dikirim. Silakan tunggu sebentar sebelum meminta ulang.',
            ]);
        }

        $code = (string) random_int(10 ** (self::CODE_LENGTH - 1), (10 ** self::CODE_LENGTH) - 1);

        $otp = PatientOtp::query()->create([
            'no_rkm_medis' => $noRkmMedis,
            'wa_number' => $waNumber,
            'code' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(self::EXPIRES_AFTER_MINUTES),
        ]);

        $this->send($noRkmMedis, $waNumber, $code, $purpose);

        return $otp;
    }

    private function resolveKnownWaNumber(string $noRkmMedis): string
    {
        $account = PatientAccount::query()->where('no_rkm_medis', $noRkmMedis)->first();

        if ($account?->isWaVerified() && $account->wa_number) {
            return $account->wa_number;
        }

        $pasien = $this->pasienRepository->findByNoRkmMedis($noRkmMedis);
        $waNumber = $pasien ? self::toWhatsAppId((string) $pasien->no_tlp) : null;

        if (! $pasien || $waNumber === null) {
            throw ValidationException::withMessages([
                'no_rkm_medis' => 'Nomor WhatsApp tidak ditemukan untuk nomor rekam medis ini. Silakan hubungi loket pendaftaran.',
            ]);
        }

        return $waNumber;
    }

    private function send(string $noRkmMedis, string $waNumber, string $code, string $purpose): void
    {
        $message = "Kode OTP SIMRS Anda: {$code}. Berlaku ".self::EXPIRES_AFTER_MINUTES.' menit. Jangan bagikan kode ini kepada siapa pun.';
        $type = 'otp_'.$purpose;

        try {
            $sent = Gowa::to($waNumber)->text($message)->send();

            PatientNotificationLog::query()->create([
                'no_rkm_medis' => $noRkmMedis,
                'wa_number' => $waNumber,
                'type' => $type,
                'message' => $message,
                'status' => 'sent',
                'gowa_message_id' => $sent->providerMessageId,
                'sent_at' => now(),
            ]);
        } catch (GowaException $exception) {
            PatientNotificationLog::query()->create([
                'no_rkm_medis' => $noRkmMedis,
                'wa_number' => $waNumber,
                'type' => $type,
                'message' => $message,
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'no_rkm_medis' => 'Gagal mengirim kode OTP melalui WhatsApp. Silakan coba lagi.',
            ]);
        }
    }

    /**
     * Normalize a loosely formatted Indonesian phone number (with or
     * without a leading 0/+62) into the MSISDN Gowa expects.
     */
    public static function toWhatsAppId(string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62'.substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62'.$digits;
        }

        return strlen($digits) >= 10 && strlen($digits) <= 15 ? $digits : null;
    }
}
