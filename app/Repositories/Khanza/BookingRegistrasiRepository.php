<?php

namespace App\Repositories\Khanza;

use App\Models\Khanza\BookingRegistrasi;
use App\Models\Khanza\Jadwal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Writes and reads SIMRS Khanza's `booking_registrasi` table — the one
 * sanctioned write target in Khanza (see the rule recorded in
 * .ai/rules/khanza.md). Used for online patient registration only.
 */
class BookingRegistrasiRepository
{
    /**
     * Whether the patient already has a booking for that date (the
     * table's real primary key is `no_rkm_medis` + `tanggal_periksa`, so
     * a second one would fail the unique constraint).
     */
    public function hasBookingOn(string $noRkmMedis, Carbon $tanggalPeriksa): bool
    {
        return BookingRegistrasi::query()
            ->where('no_rkm_medis', $noRkmMedis)
            ->whereDate('tanggal_periksa', $tanggalPeriksa)
            ->exists();
    }

    /**
     * @return Collection<int, BookingRegistrasi>
     */
    public function listForPatient(string $noRkmMedis, int $limit = 50): Collection
    {
        return BookingRegistrasi::query()
            ->where('no_rkm_medis', $noRkmMedis)
            ->orderByDesc('tanggal_periksa')
            ->limit($limit)
            ->get();
    }

    public function create(
        string $noRkmMedis,
        string $kdDokter,
        string $kdPoli,
        Carbon $tanggalPeriksa,
        string $kdPj,
        Jadwal $jadwal,
    ): BookingRegistrasi {
        $now = Carbon::now();

        return BookingRegistrasi::query()->create([
            'tanggal_booking' => $now->toDateString(),
            'jam_booking' => $now->toTimeString(),
            'no_rkm_medis' => $noRkmMedis,
            'tanggal_periksa' => $tanggalPeriksa->toDateString(),
            'kd_dokter' => $kdDokter,
            'kd_poli' => $kdPoli,
            'no_reg' => $this->nextNoReg($kdPoli, $tanggalPeriksa),
            'kd_pj' => $kdPj,
            'limit_reg' => 0,
            'waktu_kunjungan' => $tanggalPeriksa->copy()->setTimeFromTimeString($jadwal->jam_mulai),
            'status' => 'Belum',
        ]);
    }

    private function nextNoReg(string $kdPoli, Carbon $tanggalPeriksa): string
    {
        $count = BookingRegistrasi::query()
            ->where('kd_poli', $kdPoli)
            ->whereDate('tanggal_periksa', $tanggalPeriksa)
            ->count();

        return str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }
}
