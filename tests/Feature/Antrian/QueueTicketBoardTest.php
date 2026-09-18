<?php

namespace Tests\Feature\Antrian;

use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueTicketBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_queue_board(): void
    {
        $this->get('/admin/queue-tickets')->assertRedirect('/admin/login');
    }

    public function test_an_authenticated_admin_can_view_the_queue_board(): void
    {
        $ticket = QueueTicket::query()->create([
            'no_rawat' => '2026/09/18/000001',
            'no_rkm_medis' => '000123',
            'kd_poli' => 'POLI1',
            'kd_dokter' => 'DOK1',
            'tanggal' => today(),
            'queue_number' => 7,
            'status' => 'waiting',
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/queue-tickets')
            ->assertOk()
            ->assertSee($ticket->queue_number);
    }
}
