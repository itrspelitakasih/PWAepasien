<?php

namespace App\Repositories\Khanza;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates a patient's certificate letters ("surat keterangan") across
 * SIMRS Khanza's per-letter-type tables into a single chronological history.
 *
 * Khanza has no single "surat" history table — each letter type (surat
 * sakit, surat keterangan sehat, surat rawat inap, ...) lives in its own
 * table keyed by `no_rawat`, plus Surat Kontrol which lives in `skdp_bpjs`
 * keyed directly by `no_rkm_medis` (see historyForPatient()). Internal
 * admin/consent documents (surat_masuk, surat_keluar, surat_persetujuan_*,
 * surat_pernyataan_*, ...) are intentionally excluded — they aren't letters
 * issued to the patient. Note: the BPJS bridging tables (bridging_sep,
 * bridging_surat_kontrol_bpjs, ...) are NOT used here — they're unused
 * across this Khanza install (0 rows in every bridging_* table).
 *
 * Reads only, via the query builder against the `sik` connection (per
 * .ai/rules/khanza.md); never writes.
 */
class SuratRepository
{
    /**
     * Table => [date column, display label] for each patient-facing
     * certificate type.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const LETTER_TYPES = [
        'suratsakit' => ['tanggalawal', 'Surat Keterangan Sakit'],
        'surat_keterangan_sehat' => ['tanggalsurat', 'Surat Keterangan Sehat'],
        'surat_keterangan_berobat' => ['tanggal_surat', 'Surat Keterangan Berobat'],
        'surat_keterangan_rawat_inap' => ['tanggalawal', 'Surat Keterangan Rawat Inap'],
        'surat_bebas_tato' => ['tanggalperiksa', 'Surat Bebas Tato'],
        'surat_bebas_tbc' => ['tanggalsurat', 'Surat Bebas TBC'],
        'surat_buta_warna' => ['tanggalperiksa', 'Surat Buta Warna'],
        'surat_hamil' => ['tanggalperiksa', 'Surat Keterangan Hamil'],
        'surat_cuti_hamil' => ['terhitung_mulai', 'Surat Cuti Hamil'],
        'surat_keterangan_covid' => ['berlakumulai', 'Surat Keterangan Covid-19'],
        'surat_keterangan_layak_terbang' => ['tanggal_periksa', 'Surat Keterangan Layak Terbang'],
        'surat_kewaspadaan_kesehatan' => ['tanggalperiksa', 'Surat Kewaspadaan Kesehatan'],
        'surat_skbn' => ['tanggalsurat', 'Surat Keterangan Bebas Narkoba'],
    ];

    /**
     * All certificate letters issued to a patient across every letter type,
     * newest first.
     *
     * @return Collection<int, object{no_surat: string, no_rawat: string, tanggal: string, jenis: string}>
     */
    public function historyForPatient(string $noRkmMedis, int $limit = 100): Collection
    {
        $queries = [];

        foreach (self::LETTER_TYPES as $table => [$dateColumn, $label]) {
            $queries[] = DB::connection('sik')
                ->table($table)
                ->join('reg_periksa', "$table.no_rawat", '=', 'reg_periksa.no_rawat')
                ->where('reg_periksa.no_rkm_medis', $noRkmMedis)
                ->selectRaw(
                    "$table.no_surat as no_surat, $table.no_rawat as no_rawat, $table.$dateColumn as tanggal, ? as jenis",
                    [$label],
                );
        }

        // skdp_bpjs (Surat Kontrol) doesn't fit the pattern above: its letter
        // number lives in `no_antrian` (not `no_surat`), it has no `no_rawat`
        // column, and it's keyed directly by `no_rkm_medis` rather than via
        // reg_periksa.
        $queries[] = DB::connection('sik')
            ->table('skdp_bpjs')
            ->where('no_rkm_medis', $noRkmMedis)
            ->selectRaw(
                "no_antrian as no_surat, '' as no_rawat, tanggal_rujukan as tanggal, ? as jenis",
                ['Surat Kontrol'],
            );

        $base = array_shift($queries);

        foreach ($queries as $query) {
            $base->unionAll($query);
        }

        return $base->orderByDesc('tanggal')->limit($limit)->get();
    }
}
