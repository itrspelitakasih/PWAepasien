<?php

namespace App\Repositories\Khanza;

use App\Models\Khanza\BookingRegistrasi;
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
     * One entry per doctor/poli for a given date, ordered by first start
     * time and optionally capped to $limit — used for the patient portal's
     * "jadwal dokter hari ini" homepage widget. A doctor who practices in several
     * sessions the same day at the same poli gets a single entry listing
     * every session in `sesi`, with the quota summed across sessions.
     * `jadwal` is the earliest session. Empty when the date is a Khanza-wide
     * holiday.
     *
     * @return Collection<int, array{jadwal: Jadwal, sesi: array<int, array{jam_mulai: string, jam_selesai: string}>, sisa_kuota: int}>
     */
    public function forDate(Carbon $date, ?int $limit = null): Collection
    {
        if ($this->isHoliday($date)) {
            return collect();
        }

        $hariKerja = self::HARI_KERJA[$date->isoWeekday()];

        return Jadwal::query()
            ->where('hari_kerja', $hariKerja)
            ->orderBy('jam_mulai')
            ->get()
            ->groupBy(fn (Jadwal $jadwal): string => $jadwal->kd_dokter.'|'.$jadwal->kd_poli)
            ->map(function (Collection $sessions) use ($date): array {
                /** @var Jadwal $first */
                $first = $sessions->first();

                return [
                    'jadwal' => $first,
                    'sesi' => $sessions
                        ->map(fn (Jadwal $jadwal): array => [
                            'jam_mulai' => $jadwal->jam_mulai,
                            'jam_selesai' => $jadwal->jam_selesai,
                        ])
                        ->values()
                        ->all(),
                    'sisa_kuota' => max(0, $sessions->sum('kuota') - $this->registeredCount($first->kd_dokter, $first->kd_poli, $date)),
                ];
            })
            ->values()
            ->when($limit !== null, fn (Collection $entries): Collection => $entries->take($limit));
    }

    public function isHoliday(Carbon $date): bool
    {
        return DB::connection('sik')
            ->table('set_hari_libur')
            ->whereDate('tanggal', $date)
            ->exists();
    }

    /**
     * Registrations already taking a slot: real `reg_periksa` rows plus
     * online bookings loket hasn't processed yet (status "Belum"), which
     * become `reg_periksa` rows later — so nothing is counted twice.
     */
    private function registeredCount(string $kdDokter, string $kdPoli, Carbon $date): int
    {
        $registered = RegPeriksa::query()
            ->where('kd_dokter', $kdDokter)
            ->where('kd_poli', $kdPoli)
            ->whereDate('tgl_registrasi', $date)
            ->count();

        $pendingBookings = BookingRegistrasi::query()
            ->where('kd_dokter', $kdDokter)
            ->where('kd_poli', $kdPoli)
            ->whereDate('tanggal_periksa', $date)
            ->where('status', 'Belum')
            ->count();

        return $registered + $pendingBookings;
    }
}
