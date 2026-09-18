<?php

namespace App\Repositories\Khanza;

use App\Models\Khanza\Penjab;
use Illuminate\Support\Collection;

/**
 * Isolates read queries against SIMRS Khanza's `penjab` (payer) table.
 * Only used as a fallback picker for online registration when a patient
 * has no prior visit to infer `kd_pj` from.
 */
class PenjabRepository
{
    public function find(string $kdPj): ?Penjab
    {
        return Penjab::query()->find($kdPj);
    }

    /**
     * @return Collection<int, Penjab>
     */
    public function active(): Collection
    {
        return Penjab::query()
            ->where('status', '1')
            ->orderBy('png_jawab')
            ->get();
    }
}
