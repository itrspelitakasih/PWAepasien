<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\PatientAccount;
use App\Models\QueueTicket;
use App\Repositories\Khanza\DokterRepository;
use App\Repositories\Khanza\JadwalRepository;
use App\Repositories\Khanza\PoliklinikRepository;
use App\Services\Antrian\QueueTicketService;
use App\Services\Patients\PatientAuthService;
use App\Services\Patients\PatientOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PatientPortalController extends Controller
{
    public function __construct(
        private readonly PatientOtpService $otpService,
        private readonly PatientAuthService $patientAuthService,
        private readonly JadwalRepository $jadwalRepository,
        private readonly DokterRepository $dokterRepository,
        private readonly PoliklinikRepository $poliklinikRepository,
        private readonly QueueTicketService $queueTicketService,
    ) {}

    public function welcome(): View
    {
        return view('patient.welcome');
    }

    public function index(Request $request): View
    {
        /** @var PatientAccount $account */
        $account = $request->user('pasien');

        $jadwalHariIni = $this->jadwalRepository->forDate(Carbon::today());

        $antrianAktif = QueueTicket::query()
            ->where('no_rkm_medis', $account->no_rkm_medis)
            ->whereIn('status', ['waiting', 'called'])
            ->whereDate('tanggal', Carbon::today())
            ->orderBy('queue_number')
            ->first();

        return view('patient.dashboard', [
            'account' => $account,
            'jadwalHariIni' => $jadwalHariIni,
            'dokters' => $this->dokterRepository->findMany($jadwalHariIni->pluck('jadwal.kd_dokter')->all()),
            'polis' => $this->poliklinikRepository->findMany($jadwalHariIni->pluck('jadwal.kd_poli')->all()),
            'antrianAktif' => $antrianAktif,
            'antrianPoli' => $antrianAktif ? $this->poliklinikRepository->find($antrianAktif->kd_poli) : null,
            'antrianPosisi' => $antrianAktif ? $this->queueTicketService->positionAhead($antrianAktif) : null,
            'antrianEta' => $antrianAktif ? $this->queueTicketService->etaMinutes($antrianAktif) : null,
        ]);
    }

    public function edit(Request $request): View
    {
        return view('patient.settings', [
            'account' => $request->user('pasien'),
        ]);
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $request->validate([
            'notify_appointment' => ['sometimes', 'boolean'],
            'notify_queue' => ['sometimes', 'boolean'],
            'notify_lab_result' => ['sometimes', 'boolean'],
        ]);

        /** @var PatientAccount $account */
        $account = $request->user('pasien');
        $account->update([
            'notify_appointment' => $request->boolean('notify_appointment'),
            'notify_queue' => $request->boolean('notify_queue'),
            'notify_lab_result' => $request->boolean('notify_lab_result'),
        ]);

        return back()->with('status', 'Preferensi notifikasi tersimpan.');
    }

    public function updateTheme(Request $request): RedirectResponse|Response
    {
        $data = $request->validate([
            'theme' => ['required', 'in:light,dark,system'],
        ]);

        /** @var PatientAccount $account */
        $account = $request->user('pasien');
        $account->update(['theme' => $data['theme']]);

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return back()->with('status', 'Preferensi tampilan tersimpan.');
    }

    public function requestWaNumberChange(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'wa_number' => ['required', 'string'],
        ]);

        /** @var PatientAccount $account */
        $account = $request->user('pasien');

        $this->otpService->requestNumberVerificationOtp($account->no_rkm_medis, $data['wa_number']);

        return back()->with('status', 'Kode OTP dikirim ke nomor baru Anda.');
    }

    public function confirmWaNumberChange(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        /** @var PatientAccount $account */
        $account = $request->user('pasien');

        if (! $this->otpService->verify($account->no_rkm_medis, $data['code'], 'link_wa')) {
            throw ValidationException::withMessages([
                'code' => 'Kode OTP salah atau sudah kedaluwarsa.',
            ]);
        }

        $this->patientAuthService->confirmWaNumber($account);

        return redirect()->route('patient.settings')->with('status', 'Nomor WhatsApp berhasil diverifikasi.');
    }
}
