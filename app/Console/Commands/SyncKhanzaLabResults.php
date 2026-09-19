<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SendLabResultNotification;
use App\Models\LabResultNotification;
use App\Repositories\Khanza\PermintaanLabRepository;
use Illuminate\Console\Command;

class SyncKhanzaLabResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lab:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poll Khanza permintaan_lab for lab requests finished today and notify the patient once per request';

    public function handle(PermintaanLabRepository $permintaanLabRepository): int
    {
        $finished = $permintaanLabRepository->finishedSince(today());

        $known = LabResultNotification::query()
            ->whereIn('noorder', $finished->pluck('noorder'))
            ->pluck('noorder')
            ->all();

        $created = 0;

        foreach ($finished->reject(fn (object $row): bool => in_array($row->noorder, $known, true)) as $row) {
            $notification = LabResultNotification::query()->create([
                'noorder' => $row->noorder,
                'no_rawat' => $row->no_rawat,
                'no_rkm_medis' => $row->no_rkm_medis,
                'resulted_at' => $row->resulted_at,
            ]);

            SendLabResultNotification::dispatch($notification->id);
            $created++;
        }

        $this->info(sprintf('Announced %d finished lab result(s).', $created));

        return self::SUCCESS;
    }
}
