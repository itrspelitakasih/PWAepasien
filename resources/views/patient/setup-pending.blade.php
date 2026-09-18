@extends('layouts.patient', ['title' => 'Portal Belum Siap'])

@section('content')
    <h1 class="mb-1 text-xl font-semibold">Portal Belum Siap Digunakan</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Aplikasi ini belum selesai dikonfigurasi oleh admin, sehingga portal pasien belum bisa diakses.
        Silakan hubungi admin untuk melengkapi pengaturan koneksi WhatsApp (GOWA) dan database SIMRS Khanza
        pada halaman <span class="font-medium">Pengaturan Aplikasi</span>, lalu coba lagi.
    </p>
@endsection
