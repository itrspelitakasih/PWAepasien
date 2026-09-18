@extends('layouts.patient-app', ['title' => 'Riwayat Kunjungan'])

@section('content')
    <div class="relative flex items-center gap-3 overflow-hidden bg-blue-600 px-5 py-4 text-white dark:bg-blue-800">
        <div class="relative z-10 flex items-center gap-3">
            <a href="{{ route('patient.dashboard') }}" class="rounded-full p-1 hover:bg-white/10">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h1 class="text-lg font-semibold">Riwayat Kunjungan</h1>
        </div>
    </div>

    <div class="px-5 py-4">
        @forelse ($visits as $visit)
            <a
                href="{{ route('patient.riwayat.show', ['no_rawat' => $visit->no_rawat]) }}"
                class="mb-3 flex items-center gap-3 rounded-xl border border-gray-200 p-4 text-sm hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900"
            >
                <div class="flex-1">
                    <div class="mb-1 flex items-center justify-between">
                        <span class="font-semibold">{{ $visit->tgl_registrasi->translatedFormat('d F Y') }}</span>
                        <span class="text-gray-500 dark:text-gray-400">{{ $visit->stts }}</span>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">
                        {{ $polis[$visit->kd_poli]?->nm_poli ?? $visit->kd_poli }}
                        &middot;
                        {{ $dokters[$visit->kd_dokter]?->nm_dokter ?? $visit->kd_dokter }}
                    </p>
                </div>
                <svg class="size-4 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </a>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat kunjungan.</p>
        @endforelse
    </div>
@endsection
