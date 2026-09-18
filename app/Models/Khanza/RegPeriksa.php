<?php

namespace App\Models\Khanza;

use App\Models\Khanza\Concerns\ReadOnlyFromKhanza;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * SIMRS Khanza `reg_periksa` table (connection `sik`). Read-only.
 *
 * @property string $no_rawat
 * @property string $no_rkm_medis
 * @property Carbon $tgl_registrasi
 * @property string $jam_reg
 * @property string $kd_dokter
 * @property string $kd_poli
 * @property string $kd_pj
 * @property string $stts
 * @property string $status_lanjut
 * @property string $status_poli
 */
class RegPeriksa extends Model
{
    use ReadOnlyFromKhanza;

    protected $connection = 'sik';

    protected $table = 'reg_periksa';

    protected $primaryKey = 'no_rawat';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['*'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tgl_registrasi' => 'date',
        ];
    }
}
