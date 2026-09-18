<?php

namespace App\Models\Khanza;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * SIMRS Khanza `booking_registrasi` table (connection `sik`).
 *
 * Unlike every other model under App\Models\Khanza, this one is NOT
 * read-only. `booking_registrasi` is Khanza's own external-booking intake
 * table — loket staff process pending rows into a real `reg_periksa`
 * registration from the actual Khanza desktop app. This app is only
 * allowed to INSERT here for online patient registration; see the
 * `booking_registrasi is the one sanctioned Khanza write target` rule in
 * .ai/rules/khanza.md before touching this model.
 *
 * Real primary key is composite (no_rkm_medis, tanggal_periksa) — a
 * patient can only have one booking per date; inserting a duplicate
 * throws a DB unique-constraint exception.
 *
 * @property string $no_rkm_medis
 * @property Carbon $tanggal_booking
 * @property string $jam_booking
 * @property Carbon $tanggal_periksa
 * @property string $kd_dokter
 * @property string $kd_poli
 * @property string $no_reg
 * @property string $kd_pj
 * @property int $limit_reg
 * @property Carbon $waktu_kunjungan
 * @property string $status
 */
class BookingRegistrasi extends Model
{
    protected $connection = 'sik';

    protected $table = 'booking_registrasi';

    protected $primaryKey = 'no_rkm_medis';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'tanggal_booking',
        'jam_booking',
        'no_rkm_medis',
        'tanggal_periksa',
        'kd_dokter',
        'kd_poli',
        'no_reg',
        'kd_pj',
        'limit_reg',
        'waktu_kunjungan',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_booking' => 'date',
            'tanggal_periksa' => 'date',
            'waktu_kunjungan' => 'datetime',
        ];
    }
}
