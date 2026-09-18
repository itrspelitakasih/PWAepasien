<?php

namespace App\Models\Khanza;

use App\Models\Khanza\Concerns\ReadOnlyFromKhanza;
use Illuminate\Database\Eloquent\Model;

/**
 * SIMRS Khanza `penjab` table (connection `sik`) — payer / penanggung
 * jawab (Umum, Cash, insurance, company). Read-only.
 *
 * @property string $kd_pj
 * @property string $png_jawab
 * @property string $status
 */
class Penjab extends Model
{
    use ReadOnlyFromKhanza;

    protected $connection = 'sik';

    protected $table = 'penjab';

    protected $primaryKey = 'kd_pj';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['*'];
}
