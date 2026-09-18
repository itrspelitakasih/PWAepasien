<?php

namespace App\Models\Khanza;

use App\Models\Khanza\Concerns\ReadOnlyFromKhanza;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * SIMRS Khanza `pasien` table (connection `sik`). Read-only.
 *
 * @property string $no_rkm_medis
 * @property string $nm_pasien
 * @property string $no_ktp
 * @property string $jk
 * @property string $tmp_lahir
 * @property Carbon $tgl_lahir
 * @property string|null $alamat
 * @property string|null $no_tlp
 */
class Pasien extends Model
{
    use ReadOnlyFromKhanza;

    protected $connection = 'sik';

    protected $table = 'pasien';

    protected $primaryKey = 'no_rkm_medis';

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
            'tgl_lahir' => 'date',
            'tgl_daftar' => 'date',
        ];
    }
}
