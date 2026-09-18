<?php

namespace App\Repositories\Khanza;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates the clinical detail behind a single visit (`no_rawat`) — vitals
 * and doctor's notes, diagnoses, and prescribed medication — for the patient
 * portal's "riwayat perawatan" screen. Each piece spans a small join across
 * SIMRS Khanza tables, so like SuratRepository this queries the `sik`
 * connection via query builder rather than one-off Eloquent models.
 */
class RiwayatPerawatanRepository
{
    /**
     * Vitals/SOAP entries recorded for the visit, oldest first. Uses
     * `pemeriksaan_ranap` for inpatient visits and `pemeriksaan_ralan`
     * otherwise, matching how Khanza itself splits the two.
     *
     * @return Collection<int, object>
     */
    public function pemeriksaan(string $noRawat, string $statusLanjut): Collection
    {
        $table = $statusLanjut === 'Ranap' ? 'pemeriksaan_ranap' : 'pemeriksaan_ralan';

        return DB::connection('sik')->table($table)
            ->leftJoin('petugas', 'petugas.nip', '=', "$table.nip")
            ->where("$table.no_rawat", $noRawat)
            ->orderBy("$table.tgl_perawatan")
            ->orderBy("$table.jam_rawat")
            ->select("$table.*", 'petugas.nama as nama_petugas')
            ->get();
    }

    /**
     * Diagnoses recorded for the visit, primary first.
     *
     * @return Collection<int, object>
     */
    public function diagnosa(string $noRawat): Collection
    {
        return DB::connection('sik')->table('diagnosa_pasien')
            ->join('penyakit', 'penyakit.kd_penyakit', '=', 'diagnosa_pasien.kd_penyakit')
            ->where('diagnosa_pasien.no_rawat', $noRawat)
            ->orderBy('diagnosa_pasien.prioritas')
            ->select('penyakit.nm_penyakit', 'diagnosa_pasien.status_penyakit', 'diagnosa_pasien.prioritas')
            ->get();
    }

    /**
     * Medication prescribed during the visit.
     *
     * @return Collection<int, object>
     */
    public function resep(string $noRawat): Collection
    {
        return DB::connection('sik')->table('resep_obat')
            ->join('resep_dokter', 'resep_dokter.no_resep', '=', 'resep_obat.no_resep')
            ->join('databarang', 'databarang.kode_brng', '=', 'resep_dokter.kode_brng')
            ->where('resep_obat.no_rawat', $noRawat)
            ->orderBy('resep_obat.tgl_peresepan')
            ->orderBy('resep_obat.jam_peresepan')
            ->select('databarang.nama_brng', 'resep_dokter.jml', 'resep_dokter.aturan_pakai')
            ->get();
    }
}
