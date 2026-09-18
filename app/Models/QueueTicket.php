<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * This app's own queue state for one outpatient visit — the source of
 * truth for ordering/status ("call next / done / skip"), seeded from
 * Khanza's `reg_periksa` by the `antrian:sync` poller. Deliberately not
 * backed by Khanza's `antripoli`/`antriadmisi` tables, which have no
 * primary key, no ordering column, and are transient.
 */
class QueueTicket extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'no_rawat',
        'no_rkm_medis',
        'kd_poli',
        'kd_dokter',
        'tanggal',
        'queue_number',
        'status',
        'called_at',
        'done_at',
        'notified_issued_at',
        'notified_near_at',
        'notified_called_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'called_at' => 'datetime',
            'done_at' => 'datetime',
            'notified_issued_at' => 'datetime',
            'notified_near_at' => 'datetime',
            'notified_called_at' => 'datetime',
        ];
    }
}
