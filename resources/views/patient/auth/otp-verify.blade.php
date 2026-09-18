@extends('layouts.patient', ['title' => 'Verifikasi OTP'])

@section('content')
    <h1 class="mb-1 text-xl font-semibold">Masukkan Kode OTP</h1>
    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
        Kode OTP telah dikirim melalui WhatsApp. Kode berlaku selama 5 menit.
    </p>

    <form method="POST" action="{{ route('patient.otp.verify.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="mb-1 block text-sm font-medium">Kode OTP</label>
            <input
                type="text"
                inputmode="numeric"
                id="code"
                name="code"
                required
                autofocus
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-center text-lg tracking-widest focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800"
            >
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
        >
            Verifikasi
        </button>
    </form>

    <div class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('patient.otp.request') }}" class="font-medium text-amber-600 hover:underline dark:text-amber-400">
            Kirim ulang kode
        </a>
    </div>
@endsection
