<?php

namespace App\Models\Khanza;

use App\Models\Khanza\Concerns\ReadOnlyFromKhanza;
use Illuminate\Database\Eloquent\Model;

/**
 * SIMRS Khanza `kamar` table (connection `sik`). Read-only.
 *
 * @property string $kd_kamar
 * @property string $kd_bangsal
 * @property string $status  ISI|KOSONG|DIBERSIHKAN|DIBOOKING|PERBAIKAN
 * @property string $kelas
 * @property string $statusdata
 */
class Kamar extends Model
{
    use ReadOnlyFromKhanza;

    protected $connection = 'sik';

    protected $table = 'kamar';

    protected $primaryKey = 'kd_kamar';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['*'];
}
