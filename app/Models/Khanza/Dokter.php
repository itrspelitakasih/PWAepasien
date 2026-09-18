<?php

namespace App\Models\Khanza;

use App\Models\Khanza\Concerns\ReadOnlyFromKhanza;
use Illuminate\Database\Eloquent\Model;

/**
 * SIMRS Khanza `dokter` table (connection `sik`). Read-only.
 *
 * @property string $kd_dokter
 * @property string $nm_dokter
 * @property string $status
 */
class Dokter extends Model
{
    use ReadOnlyFromKhanza;

    protected $connection = 'sik';

    protected $table = 'dokter';

    protected $primaryKey = 'kd_dokter';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['*'];
}
