<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\QueueTicket;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PurgeOldQueueTickets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'antrian:purge {--force : Run even when automatic purge is disabled in Settings}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete queue tickets from previous days, keeping the number of days configured in Settings';

    public function handle(): int
    {
        $setting = Setting::current();

        if (! $setting->queue_purge_enabled && ! $this->option('force')) {
            $this->info('Automatic queue ticket purge is disabled in Settings.');

            return self::SUCCESS;
        }

        $cutoff = Carbon::today()->subDays((int) $setting->queue_purge_keep_days);

        $deleted = QueueTicket::query()
            ->whereDate('tanggal', '<', $cutoff)
            ->delete();

        $this->info(sprintf('Deleted %d ticket(s) dated before %s.', $deleted, $cutoff->toDateString()));

        return self::SUCCESS;
    }
}
