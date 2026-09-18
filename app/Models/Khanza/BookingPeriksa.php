<?php

namespace App\Models\Khanza;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * SIMRS Khanza `booking_periksa` table (connection `sik`).
 *
 * Unlike every other model under App\Models\Khanza, this one is NOT
 * read-only. `booking_periksa` is Khanza's own external-booking intake
 * table for people who are not yet a registered patient (no
 * `no_rkm_medis` column, unlike `booking_registrasi`) — loket staff turn
 * a pending row into an actual `pasien` + `reg_periksa` from the Khanza
 * desktop app. This app is only allowed to INSERT here for the public
 * "Daftar Pasien Baru" form; see the `booking_periksa is a sanctioned
 * Khanza write target` rule in .ai/rules/khanza.md before touching this
 * model.
 *
 * @property string $no_booking
 * @property Carbon $tanggal_booking
 * @property Carbon $tanggal
 * @property string $nama
 * @property string $alamat
 * @property string $no_telp
 * @property string|null $email
 * @property string $kd_poli
 * @property string|null $tambahan_pesan
 * @property string $status
 */
class BookingPeriksa extends Model
{
    protected $connection = 'sik';

    protected $table = 'booking_periksa';

    protected $primaryKey = 'no_booking';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'no_booking',
        'tanggal_booking',
        'tanggal',
        'nama',
        'alamat',
        'no_telp',
        'email',
        'kd_poli',
        'tambahan_pesan',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_booking' => 'date',
            'tanggal' => 'date',
        ];
    }
}
