@extends('layouts.patient-app', ['title' => 'Riwayat Surat'])

@section('content')
    <div class="relative flex items-center gap-3 overflow-hidden bg-blue-600 px-5 py-4 text-white dark:bg-blue-800">
        <div class="relative z-10 flex items-center gap-3">
            <a href="{{ route('patient.dashboard') }}" class="flex size-9 shrink-0 items-center justify-center rounded-full border border-white/50 bg-white/10 transition hover:bg-white/25">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h1 class="text-lg font-semibold">Riwayat Surat</h1>
        </div>
    </div>

    <div class="px-5 py-4">
        @forelse ($surats as $surat)
            <div class="mb-3 rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800">
                <div class="mb-1 flex items-center justify-between">
                    <span class="font-semibold">{{ $surat->jenis }}</span>
                    <span class="text-gray-500 dark:text-gray-400">
                        {{ \Illuminate\Support\Carbon::parse($surat->tanggal)->translatedFormat('d F Y') }}
                    </span>
                </div>
                <p class="text-gray-500 dark:text-gray-400">No. Surat: {{ $surat->no_surat }}</p>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat surat.</p>
        @endforelse
    </div>
@endsection
