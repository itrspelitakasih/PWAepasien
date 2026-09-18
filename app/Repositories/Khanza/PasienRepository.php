<?php

namespace App\Repositories\Khanza;

use App\Models\Khanza\Pasien;
use Illuminate\Support\Collection;

/**
 * Isolates read queries against SIMRS Khanza's `pasien` table so callers
 * never touch the `sik` connection directly.
 */
class PasienRepository
{
    public function findByNoRkmMedis(string $noRkmMedis): ?Pasien
    {
        return Pasien::query()->find($noRkmMedis);
    }

    /**
     * Batch-lookup patients by medical record number, keyed by
     * `no_rkm_medis` — avoids N+1 lookups when labelling a list of
     * visits/tickets.
     *
     * @param  array<int, string>  $noRkmMedisList
     * @return Collection<string, Pasien>
     */
    public function findMany(array $noRkmMedisList): Collection
    {
        return Pasien::query()->whereIn('no_rkm_medis', array_unique($noRkmMedisList))->get()->keyBy('no_rkm_medis');
    }

    /**
     * Verify a patient's identity the way the patient portal login does:
     * medical record number plus date of birth.
     */
    public function findByIdentity(string $noRkmMedis, string $tglLahir): ?Pasien
    {
        return Pasien::query()
            ->where('no_rkm_medis', $noRkmMedis)
            ->whereDate('tgl_lahir', $tglLahir)
            ->first();
    }
}
