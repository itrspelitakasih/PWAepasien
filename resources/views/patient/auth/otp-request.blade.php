@extends('layouts.patient', ['title' => 'Masuk dengan OTP'])

@section('content')
    <h1 class="mb-1 text-xl font-semibold">Masuk dengan OTP WhatsApp</h1>
    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
        Kami akan mengirim kode OTP ke nomor WhatsApp yang terdaftar untuk nomor rekam medis Anda.
    </p>

    <form method="POST" action="{{ route('patient.otp.send') }}" class="space-y-4">
        @csrf

        <div>
            <label for="no_rkm_medis" class="mb-1 block text-sm font-medium">Nomor Rekam Medis</label>
            <input
                type="text"
                id="no_rkm_medis"
                name="no_rkm_medis"
                value="{{ old('no_rkm_medis') }}"
                required
                autofocus
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-800"
            >
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
        >
            Kirim Kode OTP
        </button>
    </form>

    <div class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('patient.login') }}" class="font-medium text-amber-600 hover:underline dark:text-amber-400">
            Masuk dengan tanggal lahir
        </a>
    </div>
@endsection
