@extends('layouts.patient-app', ['title' => 'Profil'])

@section('content')
    <div class="relative overflow-hidden bg-blue-600 px-5 pb-8 pt-6 text-white dark:bg-blue-800">
        <h1 class="relative z-10 mb-4 text-lg font-semibold">Profil</h1>
        <div class="relative z-10 flex items-center gap-3">
            <span class="flex size-14 items-center justify-center rounded-full bg-white/20">
                <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
            </span>
            <div>
                <p class="text-base font-semibold">{{ $account->name ?? $account->no_rkm_medis }}</p>
                <p class="text-sm text-white/80">No. RM: {{ $account->no_rkm_medis }}</p>
            </div>
        </div>
    </div>

    <div class="-mt-4 px-5">
        <section class="mb-6 rounded-2xl bg-white p-5 shadow-md dark:bg-gray-900">
            <h2 class="mb-3 text-sm font-semibold text-gray-500 dark:text-gray-400">Nomor WhatsApp</h2>

            <div class="mb-4 text-sm">
                Saat ini:
                <span class="font-medium">{{ $account->wa_number ?? 'belum terhubung' }}</span>
                @if ($account->isWaVerified())
                    <span class="text-green-600 dark:text-green-400">(terverifikasi)</span>
                @endif
            </div>

            <form method="POST" action="{{ route('patient.settings.wa-number.request') }}" class="mb-4 flex gap-2">
                @csrf
                <input
                    type="text"
                    name="wa_number"
                    placeholder="08xxxxxxxxxx"
                    required
                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
                >
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Kirim OTP
                </button>
            </form>

            <form method="POST" action="{{ route('patient.settings.wa-number.verify') }}" class="flex gap-2">
                @csrf
                <input
                    type="text"
                    inputmode="numeric"
                    name="code"
                    placeholder="Kode OTP"
                    required
                    class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
                >
                <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold hover:border-blue-500 dark:border-gray-700">
                    Verifikasi
                </button>
            </form>
        </section>

        <section class="mb-6 rounded-2xl bg-white p-5 shadow-md dark:bg-gray-900">
            <h2 class="mb-3 text-sm font-semibold text-gray-500 dark:text-gray-400">Preferensi Notifikasi</h2>

            <form method="POST" action="{{ route('patient.settings.preferences') }}" class="space-y-3">
                @csrf
                @method('PUT')

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="notify_appointment" value="1" @checked($account->notify_appointment) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Pengingat jadwal kontrol
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="notify_queue" value="1" @checked($account->notify_queue) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Panggilan antrean
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="notify_lab_result" value="1" @checked($account->notify_lab_result) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    Hasil laboratorium siap
                </label>

                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                    Simpan Preferensi
                </button>
            </form>
        </section>

        <form method="POST" action="{{ route('patient.logout') }}" class="pb-6">
            @csrf
            <button type="submit" class="w-full rounded-2xl border border-red-200 bg-white px-4 py-3 text-sm font-semibold text-red-600 shadow-sm hover:bg-red-50 dark:border-red-900/50 dark:bg-gray-900 dark:hover:bg-red-900/10">
                Keluar
            </button>
        </form>
    </div>
@endsection
