<?php

namespace Tests\Feature\Radiologi;

use App\Models\PatientAccount;
use App\Repositories\Khanza\PeriksaRadiologiRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

class PatientRadiologiHistoryTest extends TestCase
{
    use RefreshDatabase;

    /** @var array{no_rawat: string, tgl: string, jam: string} */
    private array $key = ['no_rawat' => '2026/09/19/000048', 'tgl' => '2026-09-19', 'jam' => '12:22:44'];

    private function exam(): object
    {
        return (object) [
            'no_rawat' => '2026/09/19/000048',
            'tgl_periksa' => '2026-09-19',
            'jam' => '12:22:44',
            'examined_at' => Carbon::parse('2026-09-19 12:22:44'),
            'nm_dokter' => 'dr. Budi',
            'tests' => collect(['Thorax PA']),
        ];
    }

    public function test_history_lists_examinations_with_a_link_to_the_detail(): void
    {
        $account = PatientAccount::factory()->create(['no_rkm_medis' => '000123']);

        $this->mock(PeriksaRadiologiRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('historyForPatient')->with('000123')->andReturn(collect([$this->exam()]));
        });

        $this->actingAs($account, 'pasien')
            ->get(route('patient.radiologi'))
            ->assertOk()
            ->assertSee('Thorax PA')
            ->assertSee('dr. Budi')
            ->assertSee(e(route('patient.radiologi.show', $this->key)), false);
    }

    public function test_detail_shows_the_radiologist_reading(): void
    {
        $account = PatientAccount::factory()->create(['no_rkm_medis' => '000123']);
        $exam = $this->exam();

        $this->mock(PeriksaRadiologiRepository::class, function (MockInterface $mock) use ($exam): void {
            $mock->shouldReceive('findForPatient')->with('2026/09/19/000048', '2026-09-19', '12:22:44', '000123')->andReturn($exam);
            $mock->shouldReceive('bacaan')->with($exam)->andReturn('Cor dan pulmo normal.');
        });

        $this->actingAs($account, 'pasien')
            ->get(route('patient.radiologi.show', $this->key))
            ->assertOk()
            ->assertSee('Thorax PA')
            ->assertSee('Cor dan pulmo normal.');
    }

    public function test_detail_shows_images_when_the_base_url_is_configured(): void
    {
        config(['radiologi.image_base_url' => 'http://khanza.test/webapps/radiologi']);

        $account = PatientAccount::factory()->create(['no_rkm_medis' => '000123']);
        $exam = $this->exam();

        $this->mock(PeriksaRadiologiRepository::class, function (MockInterface $mock) use ($exam): void {
            $mock->shouldReceive('findForPatient')->andReturn($exam);
            $mock->shouldReceive('bacaan')->andReturnNull();
            $mock->shouldReceive('gambar')->with($exam)->andReturn(collect(['pages/upload/thorax.jpg']));
        });

        $this->actingAs($account, 'pasien')
            ->get(route('patient.radiologi.show', $this->key))
            ->assertOk()
            ->assertSee('http://khanza.test/webapps/radiologi/pages/upload/thorax.jpg', false);
    }

    public function test_detail_says_reading_is_pending_when_not_yet_entered(): void
    {
        $account = PatientAccount::factory()->create(['no_rkm_medis' => '000123']);
        $exam = $this->exam();

        $this->mock(PeriksaRadiologiRepository::class, function (MockInterface $mock) use ($exam): void {
            $mock->shouldReceive('findForPatient')->andReturn($exam);
            $mock->shouldReceive('bacaan')->andReturnNull();
        });

        $this->actingAs($account, 'pasien')
            ->get(route('patient.radiologi.show', $this->key))
            ->assertOk()
            ->assertSee('Bacaan hasil belum tersedia.');
    }

    public function test_detail_is_not_found_for_another_patients_examination(): void
    {
        $account = PatientAccount::factory()->create(['no_rkm_medis' => '000123']);

        $this->mock(PeriksaRadiologiRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('findForPatient')->andReturnNull();
        });

        $this->actingAs($account, 'pasien')
            ->get(route('patient.radiologi.show', $this->key))
            ->assertNotFound();
    }
}
