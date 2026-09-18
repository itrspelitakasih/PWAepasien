<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Services\Patients\PatientAuthService;
use App\Services\Patients\PatientOtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PatientOtpController extends Controller
{
    private const SESSION_KEY = 'patient.otp.no_rkm_medis';

    public function __construct(
        private readonly PatientOtpService $otpService,
        private readonly PatientAuthService $patientAuthService,
    ) {}

    public function create(): View
    {
        return view('patient.auth.otp-request');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'no_rkm_medis' => ['required', 'string', 'max:15'],
        ]);

        $this->otpService->requestLoginOtp($data['no_rkm_medis']);

        $request->session()->put(self::SESSION_KEY, $data['no_rkm_medis']);

        return redirect()->route('patient.otp.verify');
    }

    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has(self::SESSION_KEY)) {
            return redirect()->route('patient.otp.request');
        }

        return view('patient.auth.otp-verify');
    }

    public function update(Request $request): RedirectResponse
    {
        $noRkmMedis = $request->session()->get(self::SESSION_KEY);

        abort_unless($noRkmMedis, 419);

        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (! $this->otpService->verify($noRkmMedis, $data['code'], 'login')) {
            throw ValidationException::withMessages([
                'code' => 'Kode OTP salah atau sudah kedaluwarsa.',
            ]);
        }

        $account = $this->patientAuthService->completeOtpLogin($noRkmMedis);

        abort_unless($account, 419);

        $request->session()->forget(self::SESSION_KEY);
        Auth::guard('pasien')->login($account, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('patient.dashboard'));
    }
}
