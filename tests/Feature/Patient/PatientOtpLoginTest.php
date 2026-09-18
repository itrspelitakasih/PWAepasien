<?php

namespace Tests\Feature\Patient;

use App\Models\Khanza\Pasien;
use App\Models\PatientOtp;
use App\Repositories\Khanza\PasienRepository;
use Gowa\Sdk\Dto\SentMessage;
use Gowa\Sdk\GowaClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class PatientOtpLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_an_otp_sends_it_to_the_phone_number_on_file_in_khanza(): void
    {
        $pasien = $this->fakePasien('000123', 'Siti Aminah', '081234567890');

        $this->mock(PasienRepository::class, function ($mock) use ($pasien): void {
            $mock->shouldReceive('findByNoRkmMedis')->once()->with('000123')->andReturn($pasien);
        });

        $this->mock(GowaClient::class, function ($mock): void {
            $mock->shouldReceive('sendText')
                ->once()
                ->with('test-device', '6281234567890', \Mockery::type('string'), null)
                ->andReturn(new SentMessage('wamid.1'));
        });

        $response = $this->post(route('patient.otp.send'), ['no_rkm_medis' => '000123']);

        $response->assertRedirect(route('patient.otp.verify'));
        $this->assertSame('000123', session('patient.otp.no_rkm_medis'));

        $this->assertDatabaseHas('patient_otps', [
            'no_rkm_medis' => '000123',
            'wa_number' => '6281234567890',
            'purpose' => 'login',
        ]);

        $this->assertDatabaseHas('patient_notification_logs', [
            'no_rkm_medis' => '000123',
            'type' => 'otp_login',
            'status' => 'sent',
            'gowa_message_id' => 'wamid.1',
        ]);
    }

    public function test_requesting_an_otp_fails_when_no_phone_number_is_on_file(): void
    {
        $pasien = $this->fakePasien('000456', 'Budi', '');

        $this->mock(PasienRepository::class, function ($mock) use ($pasien): void {
            $mock->shouldReceive('findByNoRkmMedis')->once()->with('000456')->andReturn($pasien);
        });

        $response = $this->from(route('patient.otp.request'))
            ->post(route('patient.otp.send'), ['no_rkm_medis' => '000456']);

        $response->assertSessionHasErrors('no_rkm_medis');
        $this->assertDatabaseMissing('patient_otps', ['no_rkm_medis' => '000456']);
        $this->assertNull(session('patient.otp.no_rkm_medis'));
    }

    public function test_visiting_the_verify_page_without_a_pending_request_redirects_to_the_request_form(): void
    {
        $this->get(route('patient.otp.verify'))->assertRedirect(route('patient.otp.request'));
    }

    public function test_a_correct_code_logs_the_patient_in_and_links_their_whatsapp_number(): void
    {
        $capturedCode = $this->requestOtpAndCaptureCode('000123', 'Siti Aminah', '081234567890');

        $this->withSession(['patient.otp.no_rkm_medis' => '000123']);

        $this->mock(PasienRepository::class, function ($mock): void {
            $mock->shouldReceive('findByNoRkmMedis')
                ->once()
                ->with('000123')
                ->andReturn($this->fakePasien('000123', 'Siti Aminah', '081234567890'));
        });

        $response = $this->post(route('patient.otp.verify.store'), ['code' => $capturedCode]);

        $response->assertRedirect(route('patient.dashboard'));
        $this->assertTrue(Auth::guard('pasien')->check());

        $this->assertDatabaseHas('patient_accounts', [
            'no_rkm_medis' => '000123',
            'wa_number' => '6281234567890',
        ]);

        $this->assertNotNull(PatientOtp::query()->where('no_rkm_medis', '000123')->value('consumed_at'));
    }

    public function test_an_incorrect_code_fails_and_does_not_log_the_patient_in(): void
    {
        $this->requestOtpAndCaptureCode('000123', 'Siti Aminah', '081234567890');

        $this->withSession(['patient.otp.no_rkm_medis' => '000123']);

        $response = $this->from(route('patient.otp.verify'))
            ->post(route('patient.otp.verify.store'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertFalse(Auth::guard('pasien')->check());

        $this->assertSame(1, PatientOtp::query()->where('no_rkm_medis', '000123')->value('attempts'));
    }

    public function test_requesting_a_second_otp_immediately_is_throttled(): void
    {
        $pasien = $this->fakePasien('000123', 'Siti Aminah', '081234567890');

        $this->mock(PasienRepository::class, function ($mock) use ($pasien): void {
            $mock->shouldReceive('findByNoRkmMedis')->twice()->with('000123')->andReturn($pasien);
        });

        $this->mock(GowaClient::class, function ($mock): void {
            $mock->shouldReceive('sendText')->once()->andReturn(new SentMessage('wamid.1'));
        });

        $this->post(route('patient.otp.send'), ['no_rkm_medis' => '000123']);

        $response = $this->from(route('patient.otp.request'))
            ->post(route('patient.otp.send'), ['no_rkm_medis' => '000123']);

        $response->assertSessionHasErrors('no_rkm_medis');
        $this->assertSame(1, PatientOtp::query()->where('no_rkm_medis', '000123')->count());
    }

    private function requestOtpAndCaptureCode(string $noRkmMedis, string $name, string $noTlp): string
    {
        $pasien = $this->fakePasien($noRkmMedis, $name, $noTlp);
        $capturedCode = null;

        $this->mock(PasienRepository::class, function ($mock) use ($pasien, $noRkmMedis): void {
            $mock->shouldReceive('findByNoRkmMedis')->once()->with($noRkmMedis)->andReturn($pasien);
        });

        $this->mock(GowaClient::class, function ($mock) use (&$capturedCode): void {
            $mock->shouldReceive('sendText')
                ->once()
                ->andReturnUsing(function (string $deviceId, string $to, string $text) use (&$capturedCode) {
                    preg_match('/Anda: (\d+)\./', $text, $matches);
                    $capturedCode = $matches[1];

                    return new SentMessage('wamid.1');
                });
        });

        $this->post(route('patient.otp.send'), ['no_rkm_medis' => $noRkmMedis]);

        return $capturedCode;
    }

    private function fakePasien(string $noRkmMedis, string $nmPasien, string $noTlp): Pasien
    {
        $pasien = new Pasien;
        $pasien->forceFill([
            'no_rkm_medis' => $noRkmMedis,
            'nm_pasien' => $nmPasien,
            'no_tlp' => $noTlp,
        ]);

        return $pasien;
    }
}
