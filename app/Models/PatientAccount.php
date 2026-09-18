<?php

namespace App\Models;

use Database\Factories\PatientAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * The patient portal's Authenticatable identity. Maps a Khanza
 * `no_rkm_medis` to a verified WhatsApp number and notification
 * preferences; it does not duplicate clinical data from Khanza.
 */
class PatientAccount extends Authenticatable
{
    /** @use HasFactory<PatientAccountFactory> */
    use HasFactory, Notifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'no_rkm_medis',
        'name',
        'wa_number',
        'wa_verified_at',
        'notify_appointment',
        'notify_queue',
        'notify_lab_result',
        'last_login_at',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = ['remember_token'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'wa_verified_at' => 'datetime',
            'notify_appointment' => 'boolean',
            'notify_queue' => 'boolean',
            'notify_lab_result' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isWaVerified(): bool
    {
        return $this->wa_verified_at !== null;
    }

    /**
     * @return array{to: string}|null
     */
    public function routeNotificationForGowa(): ?array
    {
        return $this->wa_number ? ['to' => $this->wa_number] : null;
    }
}
