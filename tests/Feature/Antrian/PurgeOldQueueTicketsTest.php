<?php

namespace Tests\Feature\Antrian;

use App\Models\QueueTicket;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PurgeOldQueueTicketsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_does_nothing_while_the_setting_is_disabled(): void
    {
        $this->ticket('2026/09/17/000001', Carbon::yesterday()->subDay());

        $this->artisan('antrian:purge')->assertSuccessful();

        $this->assertSame(1, QueueTicket::count());
    }

    public function test_it_deletes_tickets_from_previous_days_but_keeps_today_and_later(): void
    {
        Setting::current()->update(['queue_purge_enabled' => true, 'queue_purge_keep_days' => 0]);

        $this->ticket('2026/09/17/000001', Carbon::today()->subDays(2));
        $this->ticket('2026/09/18/000001', Carbon::yesterday());
        $today = $this->ticket('2026/09/19/000001', Carbon::today());
        $tomorrow = $this->ticket('2026/09/20/000001', Carbon::tomorrow());

        $this->artisan('antrian:purge')->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            [$today->id, $tomorrow->id],
            QueueTicket::pluck('id')->all(),
        );
    }

    public function test_keep_days_retains_recent_history(): void
    {
        Setting::current()->update(['queue_purge_enabled' => true, 'queue_purge_keep_days' => 2]);

        $this->ticket('a', Carbon::today()->subDays(3));
        $keptEdge = $this->ticket('b', Carbon::today()->subDays(2));
        $keptRecent = $this->ticket('c', Carbon::yesterday());

        $this->artisan('antrian:purge')->assertSuccessful();

        $this->assertEqualsCanonicalizing(
            [$keptEdge->id, $keptRecent->id],
            QueueTicket::pluck('id')->all(),
        );
    }

    public function test_force_runs_even_when_disabled(): void
    {
        $this->ticket('2026/09/17/000001', Carbon::today()->subDays(2));

        $this->artisan('antrian:purge --force')->assertSuccessful();

        $this->assertSame(0, QueueTicket::count());
    }

    private function ticket(string $noRawat, Carbon $tanggal): QueueTicket
    {
        return QueueTicket::create([
            'no_rawat' => $noRawat,
            'no_rkm_medis' => '000001',
            'kd_poli' => 'U0001',
            'kd_dokter' => 'D0001',
            'tanggal' => $tanggal,
            'queue_number' => 1,
            'status' => 'waiting',
        ]);
    }
}
