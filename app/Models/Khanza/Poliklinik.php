<?php

namespace App\Models\Khanza;

use App\Models\Khanza\Concerns\ReadOnlyFromKhanza;
use Illuminate\Database\Eloquent\Model;

/**
 * SIMRS Khanza `poliklinik` table (connection `sik`). Read-only.
 *
 * @property string $kd_poli
 * @property string $nm_poli
 * @property string $status
 */
class Poliklinik extends Model
{
    use ReadOnlyFromKhanza;

    protected $connection = 'sik';

    protected $table = 'poliklinik';

    protected $primaryKey = 'kd_poli';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['*'];
}
