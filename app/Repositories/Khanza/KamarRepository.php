<?php

namespace App\Repositories\Khanza;

use App\Models\Khanza\Kamar;
use Illuminate\Support\Collection;

/**
 * Isolates read queries against SIMRS Khanza's `kamar` table.
 */
class KamarRepository
{
    /**
     * Available (`KOSONG`) vs total bed count per room class, over active rooms only.
     *
     * @return Collection<int, object{kelas: string, tersedia: int, total: int}>
     */
    public function availabilityByClass(): Collection
    {
        return Kamar::query()
            ->selectRaw("kelas, SUM(status = 'KOSONG') as tersedia, COUNT(*) as total")
            ->where('statusdata', '1')
            ->whereNotNull('kelas')
            ->groupBy('kelas')
            ->orderBy('kelas')
            ->toBase()
            ->get()
            ->map(fn (object $row): object => (object) [
                'kelas' => $row->kelas,
                'tersedia' => (int) $row->tersedia,
                'total' => (int) $row->total,
            ]);
    }
}
