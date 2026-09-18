<?php

namespace Tests\Feature\Api;

use App\Jobs\SendQueueNotification;
use App\Models\QueueTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AntrianCallControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('antrian.api_token', 'test-token');
    }

    public function test_a_valid_token_calls_the_matching_waiting_ticket(): void
    {
        Queue::fake();

        $ticket = QueueTicket::query()->create([
            'no_rawat' => '2026/09/18/000001',
            'no_rkm_medis' => '000001',
            'kd_poli' => 'POLI1',
            'kd_dokter' => 'DOK1',
            'tanggal' => '2026-09-18',
            'queue_number' => 1,
            'status' => 'waiting',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/antrian/panggil', ['no_rawat' => $ticket->no_rawat]);

        $response->assertOk()->assertJson(['status' => 'called']);
        $this->assertSame('called', $ticket->fresh()->status);

        Queue::assertPushed(SendQueueNotification::class, fn (SendQueueNotification $job): bool => $job->queueTicketId === $ticket->id && $job->type === 'called');
    }

    public function test_a_missing_or_wrong_token_is_rejected(): void
    {
        $ticket = QueueTicket::query()->create([
            'no_rawat' => '2026/09/18/000001',
            'no_rkm_medis' => '000001',
            'kd_poli' => 'POLI1',
            'kd_dokter' => 'DOK1',
            'tanggal' => '2026-09-18',
            'queue_number' => 1,
            'status' => 'waiting',
        ]);

        $this->postJson('/api/antrian/panggil', ['no_rawat' => $ticket->no_rawat])
            ->assertUnauthorized();

        $this->withHeader('Authorization', 'Bearer wrong-token')
            ->postJson('/api/antrian/panggil', ['no_rawat' => $ticket->no_rawat])
            ->assertUnauthorized();

        $this->assertSame('waiting', $ticket->fresh()->status);
    }

    public function test_an_unknown_no_rawat_returns_404(): void
    {
        $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/antrian/panggil', ['no_rawat' => '2026/09/18/999999'])
            ->assertNotFound();
    }

    public function test_calling_an_already_called_ticket_is_a_harmless_no_op(): void
    {
        Queue::fake();

        $ticket = QueueTicket::query()->create([
            'no_rawat' => '2026/09/18/000001',
            'no_rkm_medis' => '000001',
            'kd_poli' => 'POLI1',
            'kd_dokter' => 'DOK1',
            'tanggal' => '2026-09-18',
            'queue_number' => 1,
            'status' => 'called',
            'called_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer test-token')
            ->postJson('/api/antrian/panggil', ['no_rawat' => $ticket->no_rawat]);

        $response->assertOk()->assertJson(['status' => 'called']);

        Queue::assertNotPushed(SendQueueNotification::class);
    }
}
