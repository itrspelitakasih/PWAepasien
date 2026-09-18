<?php

namespace App\Models\Khanza;

use App\Models\Khanza\Concerns\ReadOnlyFromKhanza;
use Illuminate\Database\Eloquent\Model;

/**
 * SIMRS Khanza `jadwal` table (connection `sik`). Read-only.
 *
 * Its real primary key is composite (kd_dokter, hari_kerja, jam_mulai) and
 * Eloquent has no first-class support for that, so $primaryKey below is not
 * unique on its own — never call Jadwal::find()/save(), only query it via
 * JadwalRepository.
 *
 * @property string $kd_dokter
 * @property string $hari_kerja
 * @property string $jam_mulai
 * @property string $jam_selesai
 * @property string $kd_poli
 * @property int $kuota
 */
class Jadwal extends Model
{
    use ReadOnlyFromKhanza;

    protected $connection = 'sik';

    protected $table = 'jadwal';

    protected $primaryKey = 'kd_dokter';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['*'];
}
