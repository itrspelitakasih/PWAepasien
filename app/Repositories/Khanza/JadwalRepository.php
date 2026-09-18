<?php

namespace App\Repositories\Khanza;

use App\Models\Khanza\Jadwal;
use App\Models\Khanza\RegPeriksa;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Isolates read queries against SIMRS Khanza's `jadwal` table and computes
 * remaining quota for a given poli/date.
 */
class JadwalRepository
{
    /**
     * Khanza's `hari_kerja` enum labels, indexed by ISO weekday
     * (1 = Senin ... 7 = Akhad).
     *
     * @var array<int, string>
     */
    private const HARI_KERJA = [
        1 => 'SENIN',
        2 => 'SELASA',
        3 => 'RABU',
        4 => 'KAMIS',
        5 => 'JUMAT',
        6 => 'SABTU',
        7 => 'AKHAD',
    ];

    /**
     * Schedule rows for a poli on a given date, each with its remaining
     * quota (`kuota` minus registrations already made for that
     * doctor/poli/day). Empty when the date is a Khanza-wide holiday.
     *
     * @return Collection<int, array{jadwal: Jadwal, sisa_kuota: int}>
     */
    public function forPoliAndDate(string $kdPoli, Carbon $date): Collection
    {
        if ($this->isHoliday($date)) {
            return collect();
        }

        $hariKerja = self::HARI_KERJA[$date->isoWeekday()];

        return Jadwal::query()
            ->where('kd_poli', $kdPoli)
            ->where('hari_kerja', $hariKerja)
            ->orderBy('jam_mulai')
            ->get()
            ->map(fn (Jadwal $jadwal): array => [
                'jadwal' => $jadwal,
                'sisa_kuota' => max(0, $jadwal->kuota - $this->registeredCount($jadwal->kd_dokter, $kdPoli, $date)),
            ]);
    }

    /**
     * Schedule rows across all poli for a given date, each with its
     * remaining quota, ordered by start time and capped to $limit — used for
     * the patient portal's "jadwal dokter hari ini" homepage widget. Empty
     * when the date is a Khanza-wide holiday.
     *
     * @return Collection<int, array{jadwal: Jadwal, sisa_kuota: int}>
     */
    public function forDate(Carbon $date, int $limit = 5): Collection
    {
        if ($this->isHoliday($date)) {
            return collect();
        }

        $hariKerja = self::HARI_KERJA[$date->isoWeekday()];

        return Jadwal::query()
            ->where('hari_kerja', $hariKerja)
            ->orderBy('jam_mulai')
            ->limit($limit)
            ->get()
            ->map(fn (Jadwal $jadwal): array => [
                'jadwal' => $jadwal,
                'sisa_kuota' => max(0, $jadwal->kuota - $this->registeredCount($jadwal->kd_dokter, $jadwal->kd_poli, $date)),
            ]);
    }

    public function isHoliday(Carbon $date): bool
    {
        return DB::connection('sik')
            ->table('set_hari_libur')
            ->whereDate('tanggal', $date)
            ->exists();
    }

    private function registeredCount(string $kdDokter, string $kdPoli, Carbon $date): int
    {
        return RegPeriksa::query()
            ->where('kd_dokter', $kdDokter)
            ->where('kd_poli', $kdPoli)
            ->whereDate('tgl_registrasi', $date)
            ->count();
    }
}
