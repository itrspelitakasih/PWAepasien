<?php

namespace Tests\Feature\Antrian;

use App\Jobs\SendQueueNotification;
use App\Models\Khanza\RegPeriksa;
use App\Models\QueueTicket;
use App\Services\Antrian\QueueTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueTicketServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_issuing_tickets_assigns_sequential_numbers_per_poli_and_date(): void
    {
        Queue::fake();

        $service = app(QueueTicketService::class);

        $first = $service->issueTicket($this->regPeriksa('2026/09/18/000001', '000001'));
        $second = $service->issueTicket($this->regPeriksa('2026/09/18/000002', '000002'));

        $this->assertSame(1, $first->queue_number);
        $this->assertSame(2, $second->queue_number);
        $this->assertSame('waiting', $first->status);
        $this->assertNotNull($first->notified_issued_at);

        Queue::assertPushed(SendQueueNotification::class, fn (SendQueueNotification $job): bool => $job->queueTicketId === $first->id && $job->type === 'issued');
    }

    public function test_issuing_a_ticket_notifies_it_once_it_becomes_exactly_near_threshold_away(): void
    {
        Queue::fake();

        $service = app(QueueTicketService::class);

        $tickets = collect(range(1, 4))->map(fn (int $i): QueueTicket => $service->issueTicket(
            $this->regPeriksa(sprintf('2026/09/18/%06d', $i), sprintf('%06d', $i)),
        ));

        // near_threshold defaults to 3: only the 4th ticket (3 people waiting ahead of it) should be flagged.
        $this->assertNull($tickets[0]->fresh()->notified_near_at);
        $this->assertNull($tickets[1]->fresh()->notified_near_at);
        $this->assertNull($tickets[2]->fresh()->notified_near_at);
        $this->assertNotNull($tickets[3]->fresh()->notified_near_at);

        Queue::assertPushed(SendQueueNotification::class, fn (SendQueueNotification $job): bool => $job->queueTicketId === $tickets[3]->id && $job->type === 'near');
    }

    public function test_call_next_calls_the_oldest_waiting_ticket_and_notifies_it(): void
    {
        Queue::fake();

        $service = app(QueueTicketService::class);

        $first = $service->issueTicket($this->regPeriksa('2026/09/18/000001', '000001'));
        $service->issueTicket($this->regPeriksa('2026/09/18/000002', '000002'));

        $called = $service->callNext('POLI1', Carbon::parse('2026-09-18'));

        $this->assertSame($first->id, $called->id);
        $this->assertSame('called', $called->fresh()->status);
        $this->assertNotNull($called->fresh()->called_at);

        Queue::assertPushed(SendQueueNotification::class, fn (SendQueueNotification $job): bool => $job->queueTicketId === $first->id && $job->type === 'called');
    }

    public function test_call_next_returns_null_when_nothing_is_waiting(): void
    {
        $service = app(QueueTicketService::class);

        $this->assertNull($service->callNext('POLI1', Carbon::parse('2026-09-18')));
    }

    public function test_mark_done_and_skip_transition_status(): void
    {
        Queue::fake();

        $service = app(QueueTicketService::class);

        $ticket = $service->issueTicket($this->regPeriksa('2026/09/18/000001', '000001'));
        $service->markDone($ticket);
        $this->assertSame('done', $ticket->fresh()->status);
        $this->assertNotNull($ticket->fresh()->done_at);

        $other = $service->issueTicket($this->regPeriksa('2026/09/18/000002', '000002'));
        $service->skip($other);
        $this->assertSame('skipped', $other->fresh()->status);
    }

    public function test_position_ahead_and_eta_only_count_waiting_tickets(): void
    {
        Queue::fake();

        $service = app(QueueTicketService::class);

        $first = $service->issueTicket($this->regPeriksa('2026/09/18/000001', '000001'));
        $second = $service->issueTicket($this->regPeriksa('2026/09/18/000002', '000002'));
        $third = $service->issueTicket($this->regPeriksa('2026/09/18/000003', '000003'));

        $this->assertSame(2, $service->positionAhead($third));
        $this->assertSame(20, $service->etaMinutes($third));

        $service->markDone($service->callNext('POLI1', Carbon::parse('2026-09-18')));

        $this->assertSame(1, $service->positionAhead($third->fresh()));
    }

    private function regPeriksa(string $noRawat, string $noRkmMedis, string $kdPoli = 'POLI1', string $kdDokter = 'DOK1'): RegPeriksa
    {
        return (new RegPeriksa)->forceFill([
            'no_rawat' => $noRawat,
            'no_rkm_medis' => $noRkmMedis,
            'kd_poli' => $kdPoli,
            'kd_dokter' => $kdDokter,
            'tgl_registrasi' => Carbon::parse('2026-09-18'),
            'status_lanjut' => 'Ralan',
        ]);
    }
}
