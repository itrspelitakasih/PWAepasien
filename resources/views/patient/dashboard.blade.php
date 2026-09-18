@extends('layouts.patient-app', ['title' => 'Beranda'])

@section('content')
    <div class="relative overflow-hidden rounded-b-3xl bg-linear-to-b from-blue-500 to-blue-700 px-5 pb-14 pt-6 text-white dark:from-blue-700 dark:to-blue-900">
        <div class="relative z-10 mb-6 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Beranda</h1>
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    onclick="patientSetTheme(document.documentElement.classList.contains('dark') ? 'light' : 'dark', '{{ route('patient.settings.theme') }}')"
                    class="flex size-8 items-center justify-center rounded-full bg-white/20 text-white"
                    aria-label="Ganti mode tampilan"
                >
                    <svg class="size-5 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-6.364-.386 1.591-1.591M3 12h2.25m.386-6.364 1.591 1.591M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                    </svg>
                    <svg class="hidden size-5 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                    </svg>
                </button>

                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
            </div>
        </div>

        <div class="relative z-10 flex items-center gap-3">
            <span class="flex size-12 items-center justify-center rounded-full bg-white/20">
                <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
            </span>
            <div>
                <p class="text-base font-semibold">Hi, {{ $account->name ?? $account->no_rkm_medis }}</p>
                <p class="text-sm text-white/80">Selamat datang di {{ \App\Models\Setting::current()->app_name }}</p>
                <p class="text-xs text-white/70">No. RM: {{ $account->no_rkm_medis }}</p>
            </div>
        </div>
    </div>

    <div class="relative -mt-8 px-5">
        <div class="rounded-2xl bg-white p-5 shadow-md dark:bg-gray-900">
            @if ($antrianAktif)
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-semibold">Status Antrian</h2>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $antrianAktif->status === 'called' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' }}">
                        {{ $antrianAktif->status === 'called' ? 'Sedang Dipanggil' : 'Menunggu' }}
                    </span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $antrianAktif->queue_number }}</span>
                    <div class="min-w-0 text-sm">
                        <p class="font-medium">{{ $antrianPoli?->nm_poli ?? $antrianAktif->kd_poli }}</p>
                        @if ($antrianAktif->status === 'waiting')
                            <p class="text-gray-500 dark:text-gray-400">
                                {{ $antrianPosisi }} pasien di depan Anda &middot; &plusmn; {{ $antrianEta }} menit
                            </p>
                        @else
                            <p class="text-gray-500 dark:text-gray-400">Silakan menuju ruang periksa.</p>
                        @endif
                    </div>
                </div>
                <a
                    href="{{ route('patient.antrian') }}"
                    class="mt-4 inline-flex items-center gap-2 rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                >
                    Lihat Detail Antrian
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @else
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-base font-semibold">Status Antrian</h2>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        Belum Ada Antrian
                    </span>
                </div>
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    Anda belum memiliki antrian hari ini. Silahkan lakukan pendaftaran mandiri klinik rawat jalan.
                </p>
                <a
                    href="{{ route('patient.pendaftaran') }}"
                    class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                    </svg>
                    Daftar Antrian
                </a>
            @endif
        </div>
    </div>

    @php
        $menu = [
            ['label' => 'Jadwal Dokter', 'route' => 'patient.jadwal', 'icon' => 'calendar'],
            ['label' => 'Rawat Jalan', 'route' => 'patient.pendaftaran', 'icon' => 'stethoscope'],
            ['label' => 'Riwayat Surat', 'route' => 'patient.surat', 'icon' => 'mail'],
            ['label' => 'Riwayat Kunjungan', 'route' => 'patient.riwayat', 'icon' => 'history'],
            ['label' => 'Antrean Saya', 'route' => 'patient.antrian', 'icon' => 'queue'],
            ['label' => 'Kamar Tersedia', 'route' => null, 'icon' => 'building'],
            ['label' => 'Tarif Laborat', 'route' => null, 'icon' => 'flask'],
            ['label' => 'Tarif Radiologi', 'route' => null, 'icon' => 'scan'],
        ];

        $icons = [
            'calendar' => 'M6.75 3v2.25M17.25 3v2.25M3.75 18.75h16.5A.75.75 0 0 0 21 18V6.75a.75.75 0 0 0-.75-.75H3.75a.75.75 0 0 0-.75.75V18c0 .414.336.75.75.75Zm0 0M3 10.5h18',
            'stethoscope' => 'M11 2v2M5 2v2M5 3H4a2 2 0 0 0-2 2v4a6 6 0 0 0 12 0V5a2 2 0 0 0-2-2h-1M8 15a6 6 0 0 0 12 0v-3M18 10a2 2 0 1 0 4 0 2 2 0 0 0-4 0Z',
            'mail' => 'M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75',
            'history' => 'M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
            'queue' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z',
            'building' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
            'flask' => 'M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5.106 14.4c-1.026 1.026-.3 2.85 1.11 2.85h11.568c1.41 0 2.136-1.824 1.11-2.85l-3.985-4.99a2.25 2.25 0 0 1-.659-1.591V3.104M9.75 3.104h4.5M9.75 3.104H8.25m6 0h1.5',
            'scan' => 'M3.75 7.5V6A2.25 2.25 0 0 1 6 3.75h1.5m9 0H18A2.25 2.25 0 0 1 20.25 6v1.5m0 9V18a2.25 2.25 0 0 1-2.25 2.25h-1.5m-9 0H6A2.25 2.25 0 0 1 3.75 18v-1.5M3 12h18',
        ];
    @endphp

    <div class="mt-6 grid grid-cols-4 gap-x-2 gap-y-5 px-5 text-center">
        @foreach ($menu as $item)
            @if ($item['route'])
                <a href="{{ route($item['route']) }}" class="flex flex-col items-center gap-2">
                    <span class="flex size-14 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icon']] }}" />
                        </svg>
                    </span>
                    <span class="text-xs font-medium leading-tight text-gray-700 dark:text-gray-300">{{ $item['label'] }}</span>
                </a>
            @else
                <span class="flex flex-col items-center gap-2 opacity-50" title="Segera hadir">
                    <span class="flex size-14 items-center justify-center rounded-2xl bg-gray-300 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icon']] }}" />
                        </svg>
                    </span>
                    <span class="text-xs font-medium leading-tight text-gray-500 dark:text-gray-400">{{ $item['label'] }}</span>
                </span>
            @endif
        @endforeach
    </div>

    <div class="mt-8 pb-6">
        <h2 class="mb-3 px-5 text-base font-semibold">Jadwal Dokter Hari Ini</h2>

        <div data-drag-scroll class="flex cursor-grab snap-x snap-mandatory gap-3 overflow-x-auto scrollbar-none pb-1 active:cursor-grabbing">
            @forelse ($jadwalHariIni as $entry)
                @php
                    $namaDokter = $dokters[$entry['jadwal']->kd_dokter]?->nm_dokter ?? $entry['jadwal']->kd_dokter;
                    $initials = collect(preg_split('/\s+/', trim(preg_replace('/^dr\.?\s*/i', '', $namaDokter))))
                        ->filter()
                        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
                        ->take(2)
                        ->implode('');
                @endphp
                <div class="flex w-60 shrink-0 snap-start items-start gap-2.5 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm first:ml-5 last:mr-5 dark:border-gray-800 dark:bg-gray-900">
                    <div class="relative flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                        {{ $initials }}
                        <span
                            class="absolute -right-0.5 -top-0.5 size-2.5 rounded-full border-2 border-white {{ $entry['sisa_kuota'] > 0 ? 'bg-green-500' : 'bg-red-500' }} dark:border-gray-900"
                            title="Sisa kuota: {{ $entry['sisa_kuota'] }}"
                        ></span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold leading-tight">{{ $namaDokter }}</p>
                        <p class="mt-1 text-[11px] uppercase tracking-wide text-gray-400 dark:text-gray-500">
                            {{ $polis[$entry['jadwal']->kd_poli]?->nm_poli ?? $entry['jadwal']->kd_poli }}
                        </p>
                        <p class="mt-1.5 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                            <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z" />
                            </svg>
                            {{ \Illuminate\Support\Carbon::parse($entry['jadwal']->jam_mulai)->format('H:i') }}
                            &ndash;
                            {{ \Illuminate\Support\Carbon::parse($entry['jadwal']->jam_selesai)->format('H:i') }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="ml-5 text-sm text-gray-500 dark:text-gray-400">Tidak ada jadwal dokter hari ini.</p>
            @endforelse
        </div>
    </div>
@endsection
