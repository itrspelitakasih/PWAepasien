<?php

namespace Tests\Feature\Antrian;

use App\Jobs\SendQueueNotification;
use App\Models\Khanza\RegPeriksa;
use App\Models\QueueTicket;
use App\Repositories\Khanza\RegPeriksaRepository;
use App\Services\Antrian\QueueTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PatientQueueSyncTest extends TestCase
{
    use RefreshDatabase;

    private const RM = '000001';

    public function test_a_fresh_registration_gets_a_ticket_immediately(): void
    {
        Queue::fake();
        $this->visits([$this->visit('2026/09/19/000001', 'Belum')]);

        app(QueueTicketService::class)->syncPatientToday(self::RM);

        $ticket = QueueTicket::firstWhere('no_rawat', '2026/09/19/000001');
        $this->assertSame('waiting', $ticket->status);
        Queue::assertPushed(SendQueueNotification::class, fn ($job): bool => $job->type === 'issued');
    }

    public function test_no_registration_means_no_active_ticket(): void
    {
        Queue::fake();
        $stale = $this->ticket('2026/09/19/000001', 'waiting');
        $this->visits([]);

        app(QueueTicketService::class)->syncPatientToday(self::RM);

        $this->assertSame('cancelled', $stale->fresh()->status);
    }

    public function test_a_wrongly_cancelled_ticket_is_restored(): void
    {
        Queue::fake();
        $ticket = $this->ticket('2026/09/19/000001', 'cancelled');
        $this->visits([$this->visit('2026/09/19/000001', 'Belum')]);

        app(QueueTicketService::class)->syncPatientToday(self::RM);

        $this->assertSame('waiting', $ticket->fresh()->status);
    }

    public function test_an_examined_visit_is_marked_done_and_gets_no_new_ticket(): void
    {
        Queue::fake();
        $waiting = $this->ticket('2026/09/19/000001', 'waiting');
        $this->visits([
            $this->visit('2026/09/19/000001', 'Sudah'),
            $this->visit('2026/09/19/000002', 'Sudah'),
        ]);

        app(QueueTicketService::class)->syncPatientToday(self::RM);

        $this->assertSame('done', $waiting->fresh()->status);
        $this->assertNull(QueueTicket::firstWhere('no_rawat', '2026/09/19/000002'));
    }

    public function test_a_cancelled_visit_cancels_its_ticket(): void
    {
        Queue::fake();
        $ticket = $this->ticket('2026/09/19/000001', 'waiting');
        $this->visits([$this->visit('2026/09/19/000001', 'Batal')]);

        app(QueueTicketService::class)->syncPatientToday(self::RM);

        $this->assertSame('cancelled', $ticket->fresh()->status);
    }

    public function test_khanza_being_unreachable_leaves_tickets_untouched(): void
    {
        $ticket = $this->ticket('2026/09/19/000001', 'waiting');
        $this->mock(RegPeriksaRepository::class, function ($mock): void {
            $mock->shouldReceive('ralanForPatientOn')->andThrow(new \RuntimeException('connection refused'));
        });

        app(QueueTicketService::class)->syncPatientToday(self::RM);

        $this->assertSame('waiting', $ticket->fresh()->status);
    }

    public function test_khanza_is_only_asked_once_within_the_throttle_window(): void
    {
        Queue::fake();
        $this->mock(RegPeriksaRepository::class, function ($mock): void {
            $mock->shouldReceive('ralanForPatientOn')->once()->andReturn(collect());
        });

        $service = app(QueueTicketService::class);
        $service->syncPatientToday(self::RM);
        $service->syncPatientToday(self::RM);
    }

    /**
     * @param  array<int, RegPeriksa>  $visits
     */
    private function visits(array $visits): void
    {
        $this->mock(RegPeriksaRepository::class, function ($mock) use ($visits): void {
            $mock->shouldReceive('ralanForPatientOn')->andReturn(collect($visits));
        });
    }

    private function visit(string $noRawat, string $stts): RegPeriksa
    {
        return (new RegPeriksa)->forceFill([
            'no_rawat' => $noRawat,
            'no_rkm_medis' => self::RM,
            'kd_poli' => 'U0001',
            'kd_dokter' => 'D0001',
            'tgl_registrasi' => Carbon::today(),
            'status_lanjut' => 'Ralan',
            'stts' => $stts,
        ]);
    }

    private function ticket(string $noRawat, string $status): QueueTicket
    {
        return QueueTicket::create([
            'no_rawat' => $noRawat,
            'no_rkm_medis' => self::RM,
            'kd_poli' => 'U0001',
            'kd_dokter' => 'D0001',
            'tanggal' => Carbon::today(),
            'queue_number' => (int) substr($noRawat, -6),
            'status' => $status,
        ]);
    }
}
