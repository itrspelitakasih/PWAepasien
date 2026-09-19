<?php

namespace App\Repositories\Khanza;

use App\Models\Khanza\RegPeriksa;
use Illuminate\Support\Collection;

/**
 * Isolates read queries against SIMRS Khanza's `reg_periksa` table.
 *
 * `no_rawat` is the table's primary key and is date-prefixed
 * ("2026/09/16/000001"), so it sorts chronologically — querying by
 * `no_rawat > cursor` is a cheap PK-range scan and avoids relying on the
 * unindexed `tgl_registrasi` column.
 */
class RegPeriksaRepository
{
    public function findByNoRawat(string $noRawat): ?RegPeriksa
    {
        return RegPeriksa::query()->find($noRawat);
    }

    /**
     * Outpatient (Ralan) registrations created after the given `no_rawat`
     * high-water mark, oldest first. Used by the queue poller.
     *
     * @return Collection<int, RegPeriksa>
     */
    public function newSince(string $cursorNoRawat, int $limit = 200): Collection
    {
        return RegPeriksa::query()
            ->where('no_rawat', '>', $cursorNoRawat)
            ->where('status_lanjut', 'Ralan')
            ->orderBy('no_rawat')
            ->limit($limit)
            ->get();
    }

    /**
     * A patient's outpatient (Ralan) registrations for one day. Lets the
     * portal show today's queue straight from Khanza instead of waiting for
     * the poller to catch up.
     *
     * @return Collection<int, RegPeriksa>
     */
    public function ralanForPatientOn(string $noRkmMedis, string $date): Collection
    {
        return RegPeriksa::query()
            ->where('no_rkm_medis', $noRkmMedis)
            ->where('tgl_registrasi', $date)
            ->where('status_lanjut', 'Ralan')
            ->orderBy('no_rawat')
            ->get();
    }

    /**
     * Current `stts` for a batch of visits, keyed by `no_rawat`. Used by
     * the poller to detect registrations cancelled in Khanza after a
     * queue ticket was already issued for them.
     *
     * @param  array<int, string>  $noRawats
     * @return Collection<string, string>
     */
    public function statusFor(array $noRawats): Collection
    {
        if ($noRawats === []) {
            return collect();
        }

        return RegPeriksa::query()
            ->whereIn('no_rawat', $noRawats)
            ->pluck('stts', 'no_rawat');
    }

    /**
     * A single visit, scoped to the owning patient so one patient can never
     * pull up another's clinical detail by guessing a `no_rawat`.
     */
    public function findForPatient(string $noRawat, string $noRkmMedis): ?RegPeriksa
    {
        return RegPeriksa::query()
            ->where('no_rawat', $noRawat)
            ->where('no_rkm_medis', $noRkmMedis)
            ->first();
    }

    /**
     * Most recent visits for a patient, newest first.
     *
     * @return Collection<int, RegPeriksa>
     */
    public function historyForPatient(string $noRkmMedis, int $limit = 50): Collection
    {
        return RegPeriksa::query()
            ->where('no_rkm_medis', $noRkmMedis)
            ->orderByDesc('tgl_registrasi')
            ->orderByDesc('jam_reg')
            ->orderByDesc('no_rawat')
            ->limit($limit)
            ->get();
    }

    /**
     * The payer (`kd_pj`) used on the patient's most recent visit, to
     * auto-fill online registration without asking them to pick one.
     */
    public function mostRecentKdPj(string $noRkmMedis): ?string
    {
        return RegPeriksa::query()
            ->where('no_rkm_medis', $noRkmMedis)
            ->orderByDesc('no_rawat')
            ->value('kd_pj');
    }
}
