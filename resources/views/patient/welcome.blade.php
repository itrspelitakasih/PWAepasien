@extends('layouts.patient', ['title' => 'Portal Pasien'])

@section('content')
    <h1 class="mb-1 text-xl font-semibold">Selamat datang</h1>
    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
        Kelola antrean, jadwal, dan riwayat kunjungan Anda secara online.
    </p>

    <div class="space-y-3">
        <a
            href="{{ route('patient.login') }}"
            class="block w-full rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-semibold text-white hover:bg-blue-700"
        >
            Masuk
        </a>

        <a
            href="{{ route('patient.register') }}"
            class="block w-full rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-semibold text-gray-700 hover:border-blue-500 hover:text-blue-600 dark:border-gray-700 dark:text-gray-200 dark:hover:border-blue-400 dark:hover:text-blue-400"
        >
            Daftar Pasien Baru
        </a>
    </div>

    <p class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">
        Sudah punya nomor rekam medis? Gunakan tombol Masuk. Belum pernah berobat di sini? Gunakan tombol Daftar Pasien Baru.
    </p>
@endsection
