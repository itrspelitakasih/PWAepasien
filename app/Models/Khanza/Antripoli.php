<?php

namespace App\Models\Khanza;

use App\Models\Khanza\Concerns\ReadOnlyFromKhanza;
use Illuminate\Database\Eloquent\Model;

/**
 * SIMRS Khanza `antripoli` table (connection `sik`) — today's live
 * outpatient queue per poli/doctor. Has no primary key or ordering column
 * and is transient (see QueueTicket for why this app's own queue state is
 * seeded from `reg_periksa` instead of read from here directly). Read-only.
 *
 * @property string $kd_dokter
 * @property string $kd_poli
 * @property string $status
 * @property string $no_rawat
 */
class Antripoli extends Model
{
    use ReadOnlyFromKhanza;

    protected $connection = 'sik';

    protected $table = 'antripoli';

    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = ['*'];
}
