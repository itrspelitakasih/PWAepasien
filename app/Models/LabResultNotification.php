<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One finished Khanza lab request (`permintaan_lab`) already announced to a
 * patient. Doubles as the de-dupe record for the `lab:sync` poller and as the
 * source of the bell-icon "hasil lab siap" entry.
 */
class LabResultNotification extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'noorder',
        'no_rawat',
        'no_rkm_medis',
        'resulted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resulted_at' => 'datetime',
        ];
    }
}
