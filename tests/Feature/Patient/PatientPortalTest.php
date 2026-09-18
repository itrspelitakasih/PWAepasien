<?php

namespace Tests\Feature\Patient;

use App\Models\PatientAccount;
use Gowa\Sdk\Dto\SentMessage;
use Gowa\Sdk\GowaClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PatientPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_the_dashboard(): void
    {
        $this->get(route('patient.dashboard'))->assertRedirect(route('patient.login'));
    }

    public function test_guest_is_redirected_to_login_from_settings(): void
    {
        $this->get(route('patient.settings'))->assertRedirect(route('patient.login'));
    }

    public function test_guest_is_redirected_to_login_from_surat_history(): void
    {
        $this->get(route('patient.surat'))->assertRedirect(route('patient.login'));
    }

    public function test_authenticated_patient_can_view_the_dashboard(): void
    {
        $account = PatientAccount::factory()->create(['no_rkm_medis' => '000123']);

        $this->actingAs($account, 'pasien')
            ->get(route('patient.dashboard'))
            ->assertOk()
            ->assertSee('000123');
    }

    public function test_authenticated_patient_can_update_notification_preferences(): void
    {
        $account = PatientAccount::factory()->create([
            'notify_appointment' => true,
            'notify_queue' => true,
            'notify_lab_result' => false,
        ]);

        $response = $this->actingAs($account, 'pasien')->put(route('patient.settings.preferences'), [
            'notify_appointment' => '1',
            'notify_lab_result' => '1',
            // notify_queue intentionally omitted, simulating an unchecked box.
        ]);

        $response->assertRedirect();
        $account->refresh();

        $this->assertTrue($account->notify_appointment);
        $this->assertFalse($account->notify_queue);
        $this->assertTrue($account->notify_lab_result);
    }

    public function test_authenticated_patient_can_request_and_confirm_a_new_whatsapp_number(): void
    {
        $account = PatientAccount::factory()->create(['no_rkm_medis' => '000123']);
        $capturedCode = null;

        $this->mock(GowaClient::class, function ($mock) use (&$capturedCode): void {
            $mock->shouldReceive('sendText')
                ->once()
                ->with('test-device', '6281299998888', \Mockery::type('string'), null)
                ->andReturnUsing(function (string $deviceId, string $to, string $text) use (&$capturedCode) {
                    preg_match('/Anda: (\d+)\./', $text, $matches);
                    $capturedCode = $matches[1];

                    return new SentMessage('wamid.2');
                });
        });

        $requestResponse = $this->actingAs($account, 'pasien')->post(route('patient.settings.wa-number.request'), [
            'wa_number' => '081299998888',
        ]);

        $requestResponse->assertRedirect();

        $this->assertDatabaseHas('patient_otps', [
            'no_rkm_medis' => '000123',
            'wa_number' => '6281299998888',
            'purpose' => 'link_wa',
        ]);

        $confirmResponse = $this->post(route('patient.settings.wa-number.verify'), [
            'code' => $capturedCode,
        ]);

        $confirmResponse->assertRedirect(route('patient.settings'));

        $account->refresh();
        $this->assertSame('6281299998888', $account->wa_number);
        $this->assertNotNull($account->wa_verified_at);
    }

    public function test_confirming_a_new_whatsapp_number_with_the_wrong_code_does_not_change_it(): void
    {
        $account = PatientAccount::factory()->create(['no_rkm_medis' => '000123']);

        $this->mock(GowaClient::class, function ($mock): void {
            $mock->shouldReceive('sendText')->once()->andReturn(new SentMessage('wamid.2'));
        });

        $this->actingAs($account, 'pasien')->post(route('patient.settings.wa-number.request'), [
            'wa_number' => '081299998888',
        ]);

        $response = $this->from(route('patient.settings'))
            ->post(route('patient.settings.wa-number.verify'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');

        $account->refresh();
        $this->assertNull($account->wa_number);
        $this->assertNull($account->wa_verified_at);
    }

    public function test_authenticated_patient_can_log_out(): void
    {
        $account = PatientAccount::factory()->create();

        $response = $this->actingAs($account, 'pasien')->post(route('patient.logout'));

        $response->assertRedirect(route('patient.login'));
        $this->assertFalse(Auth::guard('pasien')->check());
    }
}
