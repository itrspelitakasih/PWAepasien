<?php

namespace Tests\Feature\Lab;

use App\Console\Commands\SyncKhanzaLabResults;
use App\Jobs\SendLabResultNotification;
use App\Models\LabResultNotification;
use App\Models\PatientAccount;
use App\Repositories\Khanza\PermintaanLabRepository;
use App\Services\Patients\PatientNotificationFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

class LabResultNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_records_each_finished_lab_result_once_and_dispatches_a_notification(): void
    {
        Queue::fake();

        $this->mock(PermintaanLabRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('finishedSince')->twice()->andReturn(collect([
                (object) [
                    'noorder' => 'PK202609190014',
                    'no_rawat' => '2026/09/19/000048',
                    'no_rkm_medis' => '000123',
                    'resulted_at' => Carbon::parse('2026-09-19 12:22:44'),
                ],
            ]));
        });

        $this->artisan('lab:sync')->assertSuccessful();
        $this->artisan('lab:sync')->assertSuccessful();

        $this->assertSame(1, LabResultNotification::query()->count());
        Queue::assertPushed(SendLabResultNotification::class, 1);
    }

    public function test_finished_lab_result_shows_in_the_patients_notification_feed_and_unread_count(): void
    {
        $account = PatientAccount::factory()->create(['no_rkm_medis' => '000123']);

        LabResultNotification::query()->create([
            'noorder' => 'PK1',
            'no_rawat' => '2026/09/19/000048',
            'no_rkm_medis' => '000123',
            'resulted_at' => now(),
        ]);

        LabResultNotification::query()->create([
            'noorder' => 'PK2',
            'no_rawat' => '2026/09/19/000049',
            'no_rkm_medis' => '999999',
            'resulted_at' => now(),
        ]);

        $feed = app(PatientNotificationFeed::class);

        $this->assertSame(1, $feed->unreadCount($account));
        $this->assertSame(['Hasil laboratorium siap'], $feed->for($account)->pluck('title')->all());
    }
}
