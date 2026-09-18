@extends('layouts.patient', ['title' => 'Masuk Portal Pasien', 'centerLogo' => true])

@section('content')
    <h1 class="mb-1 text-xl font-semibold">Masuk Portal Pasien</h1>
    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
        Gunakan nomor rekam medis dan tanggal lahir Anda.
    </p>

    <form method="POST" action="{{ route('patient.login.store') }}" class="space-y-4">
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
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
            >
        </div>

        <div>
            <label for="tgl_lahir" class="mb-1 block text-sm font-medium">Tanggal Lahir</label>
            <input
                type="date"
                id="tgl_lahir"
                name="tgl_lahir"
                value="{{ old('tgl_lahir') }}"
                required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
            >
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
        >
            Masuk
        </button>
    </form>

    <div class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
        Tidak ingat tanggal lahir?
        <a href="{{ route('patient.otp.request') }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
            Masuk dengan OTP WhatsApp
        </a>
    </div>

    <div class="mt-2 text-center text-sm text-gray-500 dark:text-gray-400">
        Belum pernah berobat di sini?
        <a href="{{ route('patient.register') }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
            Daftar sebagai pasien baru
        </a>
    </div>
@endsection
