<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\PatientAccount;
use App\Repositories\Khanza\BookingRegistrasiRepository;
use App\Repositories\Khanza\DokterRepository;
use App\Repositories\Khanza\JadwalRepository;
use App\Repositories\Khanza\PenjabRepository;
use App\Repositories\Khanza\PoliklinikRepository;
use App\Repositories\Khanza\RegPeriksaRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PatientRegistrationController extends Controller
{
    public function __construct(
        private readonly PoliklinikRepository $poliklinikRepository,
        private readonly DokterRepository $dokterRepository,
        private readonly JadwalRepository $jadwalRepository,
        private readonly PenjabRepository $penjabRepository,
        private readonly RegPeriksaRepository $regPeriksaRepository,
        private readonly BookingRegistrasiRepository $bookingRegistrasiRepository,
    ) {}

    public function create(Request $request): View
    {
        $data = $request->validate([
            'kd_poli' => ['nullable', 'string'],
            'tanggal' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        /** @var PatientAccount $account */
        $account = $request->user('pasien');

        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : Carbon::tomorrow();
        $kdPoli = $data['kd_poli'] ?? null;

        $jadwal = $kdPoli ? $this->jadwalRepository->forPoliAndDate($kdPoli, $tanggal) : collect();
        $hasKnownPayer = $this->regPeriksaRepository->mostRecentKdPj($account->no_rkm_medis) !== null;

        return view('patient.pendaftaran', [
            'account' => $account,
            'polis' => $this->poliklinikRepository->active(),
            'kdPoli' => $kdPoli,
            'tanggal' => $tanggal,
            'jadwal' => $jadwal,
            'dokters' => $this->dokterRepository->findMany($jadwal->pluck('jadwal.kd_dokter')->all()),
            'penjabs' => $hasKnownPayer ? collect() : $this->penjabRepository->active(),
            'bookings' => $this->bookingRegistrasiRepository->listForPatient($account->no_rkm_medis),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kd_poli' => ['required', 'string'],
            'kd_dokter' => ['required', 'string'],
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'kd_pj' => ['nullable', 'string'],
        ]);

        /** @var PatientAccount $account */
        $account = $request->user('pasien');
        $tanggal = Carbon::parse($data['tanggal']);

        if ($this->bookingRegistrasiRepository->hasBookingOn($account->no_rkm_medis, $tanggal)) {
            throw ValidationException::withMessages([
                'tanggal' => 'Anda sudah memiliki pendaftaran pada tanggal tersebut.',
            ]);
        }

        $slot = $this->jadwalRepository->forPoliAndDate($data['kd_poli'], $tanggal)
            ->firstWhere('jadwal.kd_dokter', $data['kd_dokter']);

        if (! $slot || $slot['sisa_kuota'] <= 0) {
            throw ValidationException::withMessages([
                'kd_dokter' => 'Jadwal yang dipilih tidak tersedia atau kuota sudah penuh.',
            ]);
        }

        $kdPj = $this->regPeriksaRepository->mostRecentKdPj($account->no_rkm_medis) ?? $data['kd_pj'] ?? null;

        if (! $kdPj) {
            throw ValidationException::withMessages([
                'kd_pj' => 'Silakan pilih penanggung jawab pembayaran.',
            ]);
        }

        $this->bookingRegistrasiRepository->create(
            noRkmMedis: $account->no_rkm_medis,
            kdDokter: $data['kd_dokter'],
            kdPoli: $data['kd_poli'],
            tanggalPeriksa: $tanggal,
            kdPj: $kdPj,
            jadwal: $slot['jadwal'],
        );

        return redirect()->route('patient.pendaftaran')->with('status', 'Pendaftaran online berhasil dikirim. Silakan tunggu konfirmasi dari petugas loket.');
    }
}
