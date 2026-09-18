<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Repositories\Khanza\DokterRepository;
use App\Repositories\Khanza\JadwalRepository;
use App\Repositories\Khanza\PoliklinikRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PatientScheduleController extends Controller
{
    public function __construct(
        private readonly PoliklinikRepository $poliklinikRepository,
        private readonly DokterRepository $dokterRepository,
        private readonly JadwalRepository $jadwalRepository,
    ) {}

    public function index(Request $request): View
    {
        $data = $request->validate([
            'kd_poli' => ['nullable', 'string'],
            'tanggal' => ['nullable', 'date'],
        ]);

        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : Carbon::today();
        $kdPoli = $data['kd_poli'] ?? null;

        $jadwal = $kdPoli ? $this->jadwalRepository->forPoliAndDate($kdPoli, $tanggal) : collect();
        $dokters = $this->dokterRepository->findMany($jadwal->pluck('jadwal.kd_dokter')->all());

        return view('patient.jadwal', [
            'account' => $request->user('pasien'),
            'polis' => $this->poliklinikRepository->active(),
            'kdPoli' => $kdPoli,
            'tanggal' => $tanggal,
            'jadwal' => $jadwal,
            'dokters' => $dokters,
            'isHoliday' => $kdPoli ? $this->jadwalRepository->isHoliday($tanggal) : false,
        ]);
    }
}
