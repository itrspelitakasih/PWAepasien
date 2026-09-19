<?php

namespace App\Repositories\Khanza;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read queries against SIMRS Khanza's `permintaan_lab`. Khanza leaves
 * `tgl_hasil` as `0000-00-00` until the lab finishes a request, so filtering
 * on `tgl_hasil >= <date>` both excludes unfinished requests and bounds the scan.
 */
class PermintaanLabRepository
{
    /**
     * Lab requests whose result was entered on or after the given date, with
     * the owning patient's `no_rkm_medis`, oldest result first.
     *
     * @return Collection<int, object{noorder: string, no_rawat: string, no_rkm_medis: string, resulted_at: Carbon}>
     */
    public function finishedSince(Carbon $date, int $limit = 200): Collection
    {
        return DB::connection('sik')->table('permintaan_lab')
            ->join('reg_periksa', 'reg_periksa.no_rawat', '=', 'permintaan_lab.no_rawat')
            ->where('permintaan_lab.tgl_hasil', '>=', $date->toDateString())
            ->orderBy('permintaan_lab.tgl_hasil')
            ->orderBy('permintaan_lab.jam_hasil')
            ->limit($limit)
            ->select(
                'permintaan_lab.noorder',
                'permintaan_lab.no_rawat',
                'reg_periksa.no_rkm_medis',
                'permintaan_lab.tgl_hasil',
                'permintaan_lab.jam_hasil',
            )
            ->get()
            ->map(fn (object $row): object => (object) [
                'noorder' => $row->noorder,
                'no_rawat' => $row->no_rawat,
                'no_rkm_medis' => $row->no_rkm_medis,
                'resulted_at' => Carbon::parse("{$row->tgl_hasil} {$row->jam_hasil}"),
            ]);
    }

    /**
     * A patient's finished lab requests (newest first), each with the names of
     * the tests ordered under it in `tests`.
     *
     * @return Collection<int, object{noorder: string, no_rawat: string, resulted_at: Carbon, nm_dokter: ?string, diagnosa_klinis: ?string, tests: Collection<int, string>}>
     */
    public function historyForPatient(string $noRkmMedis, int $limit = 100): Collection
    {
        $requests = DB::connection('sik')->table('permintaan_lab')
            ->join('reg_periksa', 'reg_periksa.no_rawat', '=', 'permintaan_lab.no_rawat')
            ->leftJoin('dokter', 'dokter.kd_dokter', '=', 'permintaan_lab.dokter_perujuk')
            ->where('reg_periksa.no_rkm_medis', $noRkmMedis)
            ->where('permintaan_lab.tgl_hasil', '>', '0000-00-00')
            ->orderByDesc('permintaan_lab.tgl_hasil')
            ->orderByDesc('permintaan_lab.jam_hasil')
            ->limit($limit)
            ->select(
                'permintaan_lab.noorder',
                'permintaan_lab.no_rawat',
                'permintaan_lab.tgl_hasil',
                'permintaan_lab.jam_hasil',
                'permintaan_lab.diagnosa_klinis',
                'dokter.nm_dokter',
            )
            ->get();

        $tests = DB::connection('sik')->table('permintaan_pemeriksaan_lab')
            ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw', '=', 'permintaan_pemeriksaan_lab.kd_jenis_prw')
            ->whereIn('permintaan_pemeriksaan_lab.noorder', $requests->pluck('noorder'))
            ->select('permintaan_pemeriksaan_lab.noorder', 'jns_perawatan_lab.nm_perawatan')
            ->get()
            ->groupBy('noorder');

        return $requests->map(fn (object $row): object => (object) [
            'noorder' => $row->noorder,
            'no_rawat' => $row->no_rawat,
            'resulted_at' => Carbon::parse("{$row->tgl_hasil} {$row->jam_hasil}"),
            'nm_dokter' => $row->nm_dokter,
            'diagnosa_klinis' => $row->diagnosa_klinis,
            'tests' => ($tests[$row->noorder] ?? collect())->pluck('nm_perawatan')->unique()->values(),
        ]);
    }

    /**
     * One finished lab request, only if it belongs to the given patient.
     *
     * @return object{noorder: string, no_rawat: string, tgl_hasil: string, jam_hasil: string, resulted_at: Carbon, nm_dokter: ?string, diagnosa_klinis: ?string}|null
     */
    public function findForPatient(string $noorder, string $noRkmMedis): ?object
    {
        $row = DB::connection('sik')->table('permintaan_lab')
            ->join('reg_periksa', 'reg_periksa.no_rawat', '=', 'permintaan_lab.no_rawat')
            ->leftJoin('dokter', 'dokter.kd_dokter', '=', 'permintaan_lab.dokter_perujuk')
            ->where('permintaan_lab.noorder', $noorder)
            ->where('reg_periksa.no_rkm_medis', $noRkmMedis)
            ->where('permintaan_lab.tgl_hasil', '>', '0000-00-00')
            ->select(
                'permintaan_lab.noorder',
                'permintaan_lab.no_rawat',
                'permintaan_lab.tgl_hasil',
                'permintaan_lab.jam_hasil',
                'permintaan_lab.diagnosa_klinis',
                'dokter.nm_dokter',
            )
            ->first();

        if ($row === null) {
            return null;
        }

        $row->resulted_at = Carbon::parse("{$row->tgl_hasil} {$row->jam_hasil}");

        return $row;
    }

    /**
     * Result values of a finished request, grouped by test name. Khanza stamps
     * `detail_periksa_lab` with the same no_rawat + tgl/jam as the request's
     * `tgl_hasil`/`jam_hasil`, which is how the two are tied together.
     *
     * @return Collection<string, Collection<int, object{pemeriksaan: string, satuan: ?string, nilai: string, nilai_rujukan: string, keterangan: string}>>
     */
    public function hasil(object $request): Collection
    {
        return DB::connection('sik')->table('detail_periksa_lab')
            ->join('template_laboratorium', 'template_laboratorium.id_template', '=', 'detail_periksa_lab.id_template')
            ->join('jns_perawatan_lab', 'jns_perawatan_lab.kd_jenis_prw', '=', 'detail_periksa_lab.kd_jenis_prw')
            ->where('detail_periksa_lab.no_rawat', $request->no_rawat)
            ->where('detail_periksa_lab.tgl_periksa', $request->tgl_hasil)
            ->where('detail_periksa_lab.jam', $request->jam_hasil)
            ->orderBy('jns_perawatan_lab.nm_perawatan')
            ->orderBy('template_laboratorium.urut')
            ->select(
                'jns_perawatan_lab.nm_perawatan',
                'template_laboratorium.Pemeriksaan as pemeriksaan',
                'template_laboratorium.satuan',
                'detail_periksa_lab.nilai',
                'detail_periksa_lab.nilai_rujukan',
                'detail_periksa_lab.keterangan',
            )
            ->get()
            ->groupBy('nm_perawatan');
    }
}
