<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Repositories\Khanza\BookingPeriksaRepository;
use App\Repositories\Khanza\PoliklinikRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class PatientRegistrationRequestController extends Controller
{
    public function __construct(
        private readonly PoliklinikRepository $poliklinikRepository,
        private readonly BookingPeriksaRepository $bookingPeriksaRepository,
    ) {}

    public function create(): View
    {
        return view('patient.auth.register', [
            'polis' => $this->poliklinikRepository->active(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:50'],
            'alamat' => ['required', 'string', 'max:100'],
            'no_telp' => ['required', 'string', 'max:15'],
            'email' => ['nullable', 'email', 'max:50'],
            'kd_poli' => ['required', 'string'],
            'tanggal' => ['required', 'date', 'after_or_equal:today'],
            'tambahan_pesan' => ['nullable', 'string', 'max:255'],
        ]);

        $this->bookingPeriksaRepository->create(
            nama: $data['nama'],
            alamat: $data['alamat'],
            noTelp: $data['no_telp'],
            email: $data['email'] ?? null,
            kdPoli: $data['kd_poli'],
            tanggal: Carbon::parse($data['tanggal']),
            tambahanPesan: $data['tambahan_pesan'] ?? null,
        );

        return redirect()->route('patient.login')
            ->with('status', 'Pendaftaran Anda telah kami terima. Staf kami akan menghubungi Anda melalui WhatsApp untuk konfirmasi.');
    }
}
