<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Services\Patients\PatientAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PatientAuthController extends Controller
{
    public function __construct(
        private readonly PatientAuthService $patientAuthService,
    ) {}

    public function create(): View
    {
        return view('patient.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'no_rkm_medis' => ['required', 'string', 'max:15'],
            'tgl_lahir' => ['required', 'date'],
        ]);

        $account = $this->patientAuthService->attemptIdentityLogin($data['no_rkm_medis'], $data['tgl_lahir']);

        if (! $account) {
            throw ValidationException::withMessages([
                'no_rkm_medis' => 'Nomor rekam medis dan tanggal lahir tidak cocok dengan data kami.',
            ]);
        }

        Auth::guard('pasien')->login($account, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('patient.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('pasien')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('patient.login');
    }
}
