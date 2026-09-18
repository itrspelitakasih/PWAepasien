<?php

namespace App\Services\Patients;

use App\Models\Khanza\Pasien;
use App\Models\PatientAccount;
use App\Repositories\Khanza\PasienRepository;

/**
 * Turns a verified Khanza identity into a patient portal session, keeping
 * `patient_accounts` (this app's DB) in sync with Khanza's `pasien` table.
 */
class PatientAuthService
{
    public function __construct(
        private readonly PasienRepository $pasienRepository,
        private readonly PatientOtpService $otpService,
    ) {}

    /**
     * Log in by medical record number + date of birth, Khanza's own
     * identifying pair for a patient.
     */
    public function attemptIdentityLogin(string $noRkmMedis, string $tglLahir): ?PatientAccount
    {
        $pasien = $this->pasienRepository->findByIdentity($noRkmMedis, $tglLahir);

        return $pasien ? $this->syncAccount($pasien) : null;
    }

    /**
     * Complete a login after PatientOtpService::verify() succeeded for
     * purpose "login". Links the OTP's destination number as verified when
     * the account does not have one yet.
     */
    public function completeOtpLogin(string $noRkmMedis): ?PatientAccount
    {
        $pasien = $this->pasienRepository->findByNoRkmMedis($noRkmMedis);

        if (! $pasien) {
            return null;
        }

        $account = $this->syncAccount($pasien);
        $waNumber = $this->otpService->lastVerifiedWaNumber($noRkmMedis, 'login');

        if ($waNumber && ! $account->isWaVerified()) {
            $account->forceFill([
                'wa_number' => $waNumber,
                'wa_verified_at' => now(),
            ])->save();
        }

        return $account;
    }

    /**
     * Replace the account's WhatsApp number after
     * PatientOtpService::verify() succeeded for purpose "link_wa".
     */
    public function confirmWaNumber(PatientAccount $account): void
    {
        $waNumber = $this->otpService->lastVerifiedWaNumber($account->no_rkm_medis, 'link_wa');

        if (! $waNumber) {
            return;
        }

        $account->forceFill([
            'wa_number' => $waNumber,
            'wa_verified_at' => now(),
        ])->save();
    }

    private function syncAccount(Pasien $pasien): PatientAccount
    {
        $account = PatientAccount::query()->firstOrNew(['no_rkm_medis' => $pasien->no_rkm_medis]);
        $account->name = $pasien->nm_pasien;
        $account->last_login_at = now();
        $account->save();

        return $account;
    }
}
