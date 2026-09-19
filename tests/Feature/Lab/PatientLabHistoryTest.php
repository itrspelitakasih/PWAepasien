<?php

namespace Tests\Feature\Lab;

use App\Models\PatientAccount;
use App\Repositories\Khanza\PermintaanLabRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

class PatientLabHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_lists_finished_lab_requests_with_a_link_to_the_result(): void
    {
        $account = PatientAccount::factory()->create(['no_rkm_medis' => '000123']);

        $this->mock(PermintaanLabRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('historyForPatient')->with('000123')->andReturn(collect([
                (object) [
                    'noorder' => 'PK1',
                    'no_rawat' => '2026/09/19/000048',
                    'resulted_at' => Carbon::parse('2026-09-19 12:22:44'),
                    'nm_dokter' => 'dr. Budi',
                    'diagnosa_klinis' => 'DM TIPE 2',
                    'tests' => collect(['Cholesterol']),
                ],
            ]));
        });

        $this->actingAs($account, 'pasien')
            ->get(route('patient.lab'))
            ->assertOk()
            ->assertSee('Cholesterol')
            ->assertSee('dr. Budi')
            ->assertSee(route('patient.lab.show', ['noorder' => 'PK1']), false);
    }

    public function test_detail_shows_result_values_grouped_by_test(): void
    {
        $account = PatientAccount::factory()->create(['no_rkm_medis' => '000123']);

        $lab = (object) [
            'noorder' => 'PK1',
            'no_rawat' => '2026/09/19/000048',
            'tgl_hasil' => '2026-09-19',
            'jam_hasil' => '12:22:44',
            'resulted_at' => Carbon::parse('2026-09-19 12:22:44'),
            'nm_dokter' => 'dr. Budi',
            'diagnosa_klinis' => '-',
        ];

        $this->mock(PermintaanLabRepository::class, function (MockInterface $mock) use ($lab): void {
            $mock->shouldReceive('findForPatient')->with('PK1', '000123')->andReturn($lab);
            $mock->shouldReceive('hasil')->with($lab)->andReturn(collect([
                'Cholesterol' => collect([
                    (object) ['pemeriksaan' => 'Cholesterol Total', 'satuan' => 'mg/dL', 'nilai' => '180', 'nilai_rujukan' => '<200', 'keterangan' => ''],
                ]),
            ]));
        });

        $this->actingAs($account, 'pasien')
            ->get(route('patient.lab.show', ['noorder' => 'PK1']))
            ->assertOk()
            ->assertSee('Cholesterol Total')
            ->assertSee('180 mg/dL')
            ->assertSee('Rujukan: &lt;200', false);
    }

    public function test_detail_is_not_found_for_another_patients_order(): void
    {
        $account = PatientAccount::factory()->create(['no_rkm_medis' => '000123']);

        $this->mock(PermintaanLabRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('findForPatient')->with('PK9', '000123')->andReturnNull();
        });

        $this->actingAs($account, 'pasien')
            ->get(route('patient.lab.show', ['noorder' => 'PK9']))
            ->assertNotFound();
    }
}
