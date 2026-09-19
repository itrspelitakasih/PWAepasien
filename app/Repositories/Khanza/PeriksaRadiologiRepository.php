<?php

namespace App\Repositories\Khanza;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read queries against SIMRS Khanza's `periksa_radiologi`. One radiology
 * examination is identified by no_rawat + tgl_periksa + jam; it can cover
 * several procedures (one `periksa_radiologi` row each) and shares a single
 * reading in `hasil_radiologi`.
 */
class PeriksaRadiologiRepository
{
    /**
     * A patient's radiology examinations, newest first, each with the names of
     * the procedures performed in `tests`.
     *
     * @return Collection<int, object{no_rawat: string, tgl_periksa: string, jam: string, examined_at: Carbon, nm_dokter: ?string, tests: Collection<int, string>}>
     */
    public function historyForPatient(string $noRkmMedis, int $limit = 100): Collection
    {
        return DB::connection('sik')->table('periksa_radiologi')
            ->join('reg_periksa', 'reg_periksa.no_rawat', '=', 'periksa_radiologi.no_rawat')
            ->join('jns_perawatan_radiologi', 'jns_perawatan_radiologi.kd_jenis_prw', '=', 'periksa_radiologi.kd_jenis_prw')
            ->leftJoin('dokter', 'dokter.kd_dokter', '=', 'periksa_radiologi.dokter_perujuk')
            ->where('reg_periksa.no_rkm_medis', $noRkmMedis)
            ->orderByDesc('periksa_radiologi.tgl_periksa')
            ->orderByDesc('periksa_radiologi.jam')
            ->select(
                'periksa_radiologi.no_rawat',
                'periksa_radiologi.tgl_periksa',
                'periksa_radiologi.jam',
                'jns_perawatan_radiologi.nm_perawatan',
                'dokter.nm_dokter',
            )
            ->get()
            ->groupBy(fn (object $row): string => "{$row->no_rawat}|{$row->tgl_periksa}|{$row->jam}")
            ->take($limit)
            ->map(fn (Collection $rows): object => $this->examination($rows))
            ->values();
    }

    /**
     * One examination, only if it belongs to the given patient.
     *
     * @return object{no_rawat: string, tgl_periksa: string, jam: string, examined_at: Carbon, nm_dokter: ?string, tests: Collection<int, string>}|null
     */
    public function findForPatient(string $noRawat, string $tglPeriksa, string $jam, string $noRkmMedis): ?object
    {
        $rows = DB::connection('sik')->table('periksa_radiologi')
            ->join('reg_periksa', 'reg_periksa.no_rawat', '=', 'periksa_radiologi.no_rawat')
            ->join('jns_perawatan_radiologi', 'jns_perawatan_radiologi.kd_jenis_prw', '=', 'periksa_radiologi.kd_jenis_prw')
            ->leftJoin('dokter', 'dokter.kd_dokter', '=', 'periksa_radiologi.dokter_perujuk')
            ->where('periksa_radiologi.no_rawat', $noRawat)
            ->where('periksa_radiologi.tgl_periksa', $tglPeriksa)
            ->where('periksa_radiologi.jam', $jam)
            ->where('reg_periksa.no_rkm_medis', $noRkmMedis)
            ->select(
                'periksa_radiologi.no_rawat',
                'periksa_radiologi.tgl_periksa',
                'periksa_radiologi.jam',
                'jns_perawatan_radiologi.nm_perawatan',
                'dokter.nm_dokter',
            )
            ->get();

        return $rows->isEmpty() ? null : $this->examination($rows);
    }

    /**
     * The radiologist's reading of an examination, or null while not yet entered.
     */
    public function bacaan(object $exam): ?string
    {
        $hasil = DB::connection('sik')->table('hasil_radiologi')
            ->where('no_rawat', $exam->no_rawat)
            ->where('tgl_periksa', $exam->tgl_periksa)
            ->where('jam', $exam->jam)
            ->value('hasil');

        return $hasil !== null && trim($hasil) !== '' ? $hasil : null;
    }

    /**
     * Image paths (relative to Khanza's radiology folder) of an examination.
     *
     * @return Collection<int, string>
     */
    public function gambar(object $exam): Collection
    {
        return DB::connection('sik')->table('gambar_radiologi')
            ->where('no_rawat', $exam->no_rawat)
            ->where('tgl_periksa', $exam->tgl_periksa)
            ->where('jam', $exam->jam)
            ->orderBy('lokasi_gambar')
            ->pluck('lokasi_gambar');
    }

    /**
     * @param  Collection<int, object>  $rows  `periksa_radiologi` rows of one examination
     */
    private function examination(Collection $rows): object
    {
        $first = $rows->first();

        return (object) [
            'no_rawat' => $first->no_rawat,
            'tgl_periksa' => $first->tgl_periksa,
            'jam' => $first->jam,
            'examined_at' => Carbon::parse("{$first->tgl_periksa} {$first->jam}"),
            'nm_dokter' => $first->nm_dokter,
            'tests' => $rows->pluck('nm_perawatan')->unique()->values(),
        ];
    }
}
