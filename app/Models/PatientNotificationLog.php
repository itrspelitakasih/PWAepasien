<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientNotificationLog extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'no_rkm_medis',
        'wa_number',
        'type',
        'message',
        'status',
        'gowa_message_id',
        'error',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}
