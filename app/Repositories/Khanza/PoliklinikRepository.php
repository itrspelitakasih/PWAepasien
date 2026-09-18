<?php

namespace App\Repositories\Khanza;

use App\Models\Khanza\Poliklinik;
use Illuminate\Support\Collection;

/**
 * Isolates read queries against SIMRS Khanza's `poliklinik` table.
 */
class PoliklinikRepository
{
    public function find(string $kdPoli): ?Poliklinik
    {
        return Poliklinik::query()->find($kdPoli);
    }

    /**
     * Active poli, for pickers/labels.
     *
     * @return Collection<int, Poliklinik>
     */
    public function active(): Collection
    {
        return Poliklinik::query()
            ->where('status', '1')
            ->orderBy('nm_poli')
            ->get();
    }

    /**
     * Batch-lookup poli by code, keyed by `kd_poli` — avoids N+1 lookups
     * when labelling a list of visits/tickets.
     *
     * @param  array<int, string>  $kdPolis
     * @return Collection<string, Poliklinik>
     */
    public function findMany(array $kdPolis): Collection
    {
        return Poliklinik::query()->whereIn('kd_poli', array_unique($kdPolis))->get()->keyBy('kd_poli');
    }
}
