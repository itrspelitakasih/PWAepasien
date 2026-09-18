<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatientOtp extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'no_rkm_medis',
        'wa_number',
        'code',
        'purpose',
        'expires_at',
        'consumed_at',
        'attempts',
        'ip_address',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }
}
