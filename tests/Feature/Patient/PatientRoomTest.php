<?php

namespace Tests\Feature\Patient;

use App\Models\PatientAccount;
use App\Repositories\Khanza\KamarRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientRoomTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_the_room_page(): void
    {
        $this->get(route('patient.kamar'))->assertRedirect(route('patient.welcome'));
    }

    public function test_patient_sees_available_rooms_per_class(): void
    {
        $this->mock(KamarRepository::class, function ($mock): void {
            $mock->shouldReceive('availabilityByClass')->andReturn(collect([
                (object) ['kelas' => 'Kelas 1', 'tersedia' => 4, 'total' => 10],
                (object) ['kelas' => 'Kelas VIP', 'tersedia' => 0, 'total' => 2],
            ]));
        });

        $this->actingAs(PatientAccount::factory()->create(), 'pasien')
            ->get(route('patient.kamar'))
            ->assertOk()
            ->assertSee('Kelas 1')
            ->assertSee('4 tersedia')
            ->assertSee('Kelas VIP')
            ->assertSee('Penuh');
    }
}
