<?php

namespace Tests\Feature\Antrian;

use App\Models\Khanza\RegPeriksa;
use App\Models\QueueTicket;
use App\Repositories\Khanza\RegPeriksaRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SyncKhanzaQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_cancels_todays_tickets_that_are_cancelled_or_missing_in_khanza(): void
    {
        $present = $this->ticket('2026/09/19/000001', 1);
        $cancelledInKhanza = $this->ticket('2026/09/19/000002', 2);
        $missingInKhanza = $this->ticket('2026/09/19/000003', 3);

        $this->mock(RegPeriksaRepository::class, function ($mock): void {
            $mock->shouldReceive('newSince')->andReturn(collect());
            $mock->shouldReceive('statusFor')->andReturn(collect([
                '2026/09/19/000001' => 'Belum',
                '2026/09/19/000002' => 'Batal',
            ]));
        });

        $this->artisan('antrian:sync')->assertSuccessful();

        $this->assertSame('waiting', $present->fresh()->status);
        $this->assertSame('cancelled', $cancelledInKhanza->fresh()->status);
        $this->assertSame('cancelled', $missingInKhanza->fresh()->status);
    }

    public function test_it_restores_cancelled_tickets_whose_visit_exists_in_khanza(): void
    {
        $wronglyCancelled = $this->ticket('2026/09/19/000001', 1);
        $wronglyCancelled->update(['status' => 'cancelled']);
        $wasCalled = $this->ticket('2026/09/19/000002', 2);
        $wasCalled->update(['status' => 'cancelled', 'called_at' => now()]);
        $trulyCancelled = $this->ticket('2026/09/19/000003', 3);
        $trulyCancelled->update(['status' => 'cancelled']);

        $this->mock(RegPeriksaRepository::class, function ($mock): void {
            $mock->shouldReceive('newSince')->andReturn(collect());
            $mock->shouldReceive('statusFor')->andReturn(collect([
                '2026/09/19/000001' => 'Belum',
                '2026/09/19/000002' => 'Belum',
                '2026/09/19/000003' => 'Batal',
            ]));
        });

        $this->artisan('antrian:sync')
            ->expectsOutput('Issued 0 new ticket(s), cancelled 0 ticket(s), restored 2 ticket(s), finished 0 ticket(s).')
            ->assertSuccessful();

        $this->assertSame('waiting', $wronglyCancelled->fresh()->status);
        $this->assertSame('called', $wasCalled->fresh()->status);
        $this->assertSame('cancelled', $trulyCancelled->fresh()->status);
    }

    public function test_it_marks_tickets_done_when_the_visit_is_already_examined_in_khanza(): void
    {
        Queue::fake();

        $waiting = $this->ticket('2026/09/19/000001', 1);
        $called = $this->ticket('2026/09/19/000002', 2);
        $called->update(['status' => 'called', 'called_at' => now()]);
        $wronglyCancelled = $this->ticket('2026/09/19/000003', 3);
        $wronglyCancelled->update(['status' => 'cancelled']);
        $stillWaiting = $this->ticket('2026/09/19/000004', 4);

        $this->mock(RegPeriksaRepository::class, function ($mock): void {
            $mock->shouldReceive('newSince')->andReturn(collect());
            $mock->shouldReceive('statusFor')->andReturn(collect([
                '2026/09/19/000001' => 'Sudah',
                '2026/09/19/000002' => 'Sudah',
                '2026/09/19/000003' => 'Sudah',
                '2026/09/19/000004' => 'Belum',
            ]));
        });

        $this->artisan('antrian:sync')
            ->expectsOutput('Issued 0 new ticket(s), cancelled 0 ticket(s), restored 0 ticket(s), finished 3 ticket(s).')
            ->assertSuccessful();

        $this->assertSame('done', $waiting->fresh()->status);
        $this->assertNotNull($waiting->fresh()->done_at);
        $this->assertSame('done', $called->fresh()->status);
        $this->assertSame('done', $wronglyCancelled->fresh()->status);
        $this->assertSame('waiting', $stillWaiting->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_it_skips_registrations_that_already_have_a_ticket_and_issues_the_rest(): void
    {
        Queue::fake();

        $existing = $this->ticket('2026/09/19/000001', 1);
        $existing->update(['status' => 'done']);

        $this->mock(RegPeriksaRepository::class, function ($mock): void {
            $mock->shouldReceive('newSince')->andReturn(collect([
                $this->regPeriksa('2026/09/19/000001'),
                $this->regPeriksa('2026/09/19/000002'),
            ]));
            $mock->shouldReceive('statusFor')->andReturn(collect([
                '2026/09/19/000001' => 'Belum',
                '2026/09/19/000002' => 'Belum',
            ]));
        });

        $this->artisan('antrian:sync')
            ->expectsOutput('Issued 1 new ticket(s), cancelled 0 ticket(s), restored 0 ticket(s), finished 0 ticket(s).')
            ->assertSuccessful();

        $this->assertSame(2, QueueTicket::count());
        $this->assertSame('done', $existing->fresh()->status);
        $this->assertSame('waiting', QueueTicket::where('no_rawat', '2026/09/19/000002')->value('status'));
        $this->assertSame('2026/09/19/000002', Cache::get('antrian:sync:cursor'));
    }

    private function regPeriksa(string $noRawat): RegPeriksa
    {
        return (new RegPeriksa)->forceFill([
            'no_rawat' => $noRawat,
            'no_rkm_medis' => '000001',
            'kd_poli' => 'U0001',
            'kd_dokter' => 'D0001',
            'tgl_registrasi' => Carbon::today(),
            'status_lanjut' => 'Ralan',
        ]);
    }

    private function ticket(string $noRawat, int $number): QueueTicket
    {
        return QueueTicket::create([
            'no_rawat' => $noRawat,
            'no_rkm_medis' => '000001',
            'kd_poli' => 'U0001',
            'kd_dokter' => 'D0001',
            'tanggal' => Carbon::today(),
            'queue_number' => $number,
            'status' => 'waiting',
        ]);
    }
}
