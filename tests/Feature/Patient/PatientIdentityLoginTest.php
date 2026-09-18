<?php

namespace Tests\Feature\Patient;

use App\Models\Khanza\Pasien;
use App\Models\PatientAccount;
use App\Repositories\Khanza\PasienRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PatientIdentityLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_login_form(): void
    {
        $this->get(route('patient.login'))->assertOk();
    }

    public function test_already_authenticated_patient_is_redirected_away_from_login_form(): void
    {
        $account = PatientAccount::factory()->create();

        $this->actingAs($account, 'pasien')
            ->get(route('patient.login'))
            ->assertRedirect(route('patient.dashboard'));
    }

    public function test_matching_rm_and_birthdate_logs_the_patient_in_and_creates_an_account(): void
    {
        $pasien = $this->fakePasien('000123', 'Siti Aminah', '1990-05-10');

        $this->mock(PasienRepository::class, function ($mock) use ($pasien): void {
            $mock->shouldReceive('findByIdentity')
                ->once()
                ->with('000123', '1990-05-10')
                ->andReturn($pasien);
        });

        $response = $this->post(route('patient.login.store'), [
            'no_rkm_medis' => '000123',
            'tgl_lahir' => '1990-05-10',
        ]);

        $response->assertRedirect(route('patient.dashboard'));
        $this->assertTrue(Auth::guard('pasien')->check());
        $this->assertSame('000123', Auth::guard('pasien')->user()->no_rkm_medis);

        $this->assertDatabaseHas('patient_accounts', [
            'no_rkm_medis' => '000123',
            'name' => 'Siti Aminah',
        ]);
    }

    public function test_non_matching_rm_and_birthdate_fails_validation_and_does_not_log_in(): void
    {
        $this->mock(PasienRepository::class, function ($mock): void {
            $mock->shouldReceive('findByIdentity')
                ->once()
                ->with('000999', '1990-05-10')
                ->andReturnNull();
        });

        $response = $this->from(route('patient.login'))->post(route('patient.login.store'), [
            'no_rkm_medis' => '000999',
            'tgl_lahir' => '1990-05-10',
        ]);

        $response->assertRedirect(route('patient.login'));
        $response->assertSessionHasErrors('no_rkm_medis');
        $this->assertFalse(Auth::guard('pasien')->check());
        $this->assertDatabaseMissing('patient_accounts', ['no_rkm_medis' => '000999']);
    }

    public function test_login_requires_rm_and_birthdate(): void
    {
        $response = $this->post(route('patient.login.store'), []);

        $response->assertSessionHasErrors(['no_rkm_medis', 'tgl_lahir']);
    }

    private function fakePasien(string $noRkmMedis, string $nmPasien, string $tglLahir): Pasien
    {
        $pasien = new Pasien;
        $pasien->forceFill([
            'no_rkm_medis' => $noRkmMedis,
            'nm_pasien' => $nmPasien,
            'tgl_lahir' => $tglLahir,
            'no_tlp' => '081234567890',
        ]);

        return $pasien;
    }
}
