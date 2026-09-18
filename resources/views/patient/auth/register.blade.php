@extends('layouts.patient', ['title' => 'Daftar Pasien Baru', 'centerLogo' => true])

@section('content')
    <h1 class="mb-1 text-xl font-semibold">Daftar Pasien Baru</h1>
    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">
        Lengkapi data di bawah ini. Staf kami akan menghubungi Anda melalui WhatsApp untuk konfirmasi kunjungan.
    </p>

    <form method="POST" action="{{ route('patient.register.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="nama" class="mb-1 block text-sm font-medium">Nama Lengkap</label>
            <input
                type="text"
                id="nama"
                name="nama"
                value="{{ old('nama') }}"
                required
                autofocus
                maxlength="50"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
            >
        </div>

        <div>
            <label for="alamat" class="mb-1 block text-sm font-medium">Alamat</label>
            <input
                type="text"
                id="alamat"
                name="alamat"
                value="{{ old('alamat') }}"
                required
                maxlength="100"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
            >
        </div>

        <div>
            <label for="no_telp" class="mb-1 block text-sm font-medium">Nomor Telepon/WhatsApp</label>
            <input
                type="text"
                id="no_telp"
                name="no_telp"
                value="{{ old('no_telp') }}"
                required
                maxlength="15"
                placeholder="08xxxxxxxxxx"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
            >
        </div>

        <div>
            <label for="email" class="mb-1 block text-sm font-medium">Email (opsional)</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                maxlength="50"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
            >
        </div>

        <div>
            <label for="kd_poli" class="mb-1 block text-sm font-medium">Poli Tujuan</label>
            <select
                id="kd_poli"
                name="kd_poli"
                required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
            >
                <option value="" disabled {{ old('kd_poli') ? '' : 'selected' }}>Pilih poli</option>
                @foreach ($polis as $poli)
                    <option value="{{ $poli->kd_poli }}" {{ old('kd_poli') === $poli->kd_poli ? 'selected' : '' }}>
                        {{ $poli->nm_poli }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="tanggal" class="mb-1 block text-sm font-medium">Tanggal Kunjungan</label>
            <input
                type="date"
                id="tanggal"
                name="tanggal"
                value="{{ old('tanggal') }}"
                required
                min="{{ now()->toDateString() }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
            >
        </div>

        <div>
            <label for="tambahan_pesan" class="mb-1 block text-sm font-medium">Catatan (opsional)</label>
            <input
                type="text"
                id="tambahan_pesan"
                name="tambahan_pesan"
                value="{{ old('tambahan_pesan') }}"
                maxlength="255"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
            >
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
        >
            Kirim Pendaftaran
        </button>
    </form>

    <div class="mt-4 text-center text-sm text-gray-500 dark:text-gray-400">
        Sudah punya nomor rekam medis?
        <a href="{{ route('patient.login') }}" class="font-medium text-blue-600 hover:underline dark:text-blue-400">
            Masuk di sini
        </a>
    </div>
@endsection
