<?php

namespace App\Repositories\Khanza;

use App\Models\Khanza\Antripoli;
use Illuminate\Support\Collection;

/**
 * Isolates read queries against SIMRS Khanza's `antripoli` table — today's
 * live outpatient queue, used to know which poli currently have a queue
 * running rather than relying on `poliklinik`'s own active flag.
 */
class AntripoliRepository
{
    /**
     * Distinct `kd_poli` codes currently present in the queue.
     *
     * @return Collection<int, string>
     */
    public function activeKdPoli(): Collection
    {
        return Antripoli::query()->distinct()->pluck('kd_poli');
    }
}
