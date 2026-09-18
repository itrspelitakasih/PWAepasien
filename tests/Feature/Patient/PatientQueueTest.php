<?php

namespace Tests\Feature\Patient;

use App\Livewire\Patient\QueueBoard;
use App\Models\PatientAccount;
use App\Models\QueueTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PatientQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_the_queue_page(): void
    {
        $this->get(route('patient.antrian'))->assertRedirect(route('patient.login'));
    }

    public function test_patient_sees_their_own_waiting_and_called_tickets_only(): void
    {
        $account = PatientAccount::factory()->create(['no_rkm_medis' => '000123']);

        $mine = QueueTicket::query()->create([
            'no_rawat' => '2026/09/18/000001',
            'no_rkm_medis' => '000123',
            'kd_poli' => 'POLI1',
            'kd_dokter' => 'DOK1',
            'tanggal' => '2026-09-18',
            'queue_number' => 3,
            'status' => 'waiting',
        ]);

        QueueTicket::query()->create([
            'no_rawat' => '2026/09/18/000002',
            'no_rkm_medis' => '000123',
            'kd_poli' => 'POLI1',
            'kd_dokter' => 'DOK1',
            'tanggal' => '2026-09-17',
            'queue_number' => 1,
            'status' => 'done',
        ]);

        QueueTicket::query()->create([
            'no_rawat' => '2026/09/18/000099',
            'no_rkm_medis' => '999999',
            'kd_poli' => 'POLI1',
            'kd_dokter' => 'DOK1',
            'tanggal' => '2026-09-18',
            'queue_number' => 1,
            'status' => 'waiting',
        ]);

        $this->actingAs($account, 'pasien')
            ->get(route('patient.antrian'))
            ->assertOk()
            ->assertSeeLivewire(QueueBoard::class);

        Livewire::actingAs($account, 'pasien')
            ->test(QueueBoard::class)
            ->assertSee($mine->queue_number)
            ->assertViewHas('tickets', fn ($tickets) => $tickets->count() === 1 && $tickets->first()['ticket']->is($mine));
    }
}
