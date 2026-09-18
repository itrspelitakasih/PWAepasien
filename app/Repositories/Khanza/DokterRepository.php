<?php

namespace App\Repositories\Khanza;

use App\Models\Khanza\Dokter;
use Illuminate\Support\Collection;

/**
 * Isolates read queries against SIMRS Khanza's `dokter` table.
 */
class DokterRepository
{
    public function find(string $kdDokter): ?Dokter
    {
        return Dokter::query()->find($kdDokter);
    }

    /**
     * Active doctors, for pickers/labels.
     *
     * @return Collection<int, Dokter>
     */
    public function active(): Collection
    {
        return Dokter::query()
            ->where('status', '1')
            ->orderBy('nm_dokter')
            ->get();
    }

    /**
     * Batch-lookup doctors by code, keyed by `kd_dokter` — avoids N+1
     * lookups when labelling a list of visits/tickets.
     *
     * @param  array<int, string>  $kdDokters
     * @return Collection<string, Dokter>
     */
    public function findMany(array $kdDokters): Collection
    {
        return Dokter::query()->whereIn('kd_dokter', array_unique($kdDokters))->get()->keyBy('kd_dokter');
    }
}
