@extends('layouts.patient-app', ['title' => 'Riwayat Lab'])

@section('content')
    <div class="relative flex items-center gap-3 overflow-hidden bg-blue-600 px-5 py-4 text-white dark:bg-blue-800">
        <div class="relative z-10 flex items-center gap-3">
            <a href="{{ route('patient.dashboard') }}" class="flex size-9 shrink-0 items-center justify-center rounded-full border border-white/50 bg-white/10 transition hover:bg-white/25">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h1 class="text-lg font-semibold">Riwayat Lab</h1>
        </div>
    </div>

    <div class="px-5 py-4">
        @forelse ($labs as $lab)
            <a
                href="{{ route('patient.lab.show', ['noorder' => $lab->noorder]) }}"
                class="mb-3 block rounded-xl border border-gray-200 p-4 text-sm hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900"
            >
                <div class="mb-1 flex items-center justify-between">
                    <span class="font-semibold">{{ $lab->resulted_at->translatedFormat('d F Y') }}</span>
                    <span class="text-gray-500 dark:text-gray-400">{{ $lab->resulted_at->format('H:i') }}</span>
                </div>

                @if ($lab->tests->isNotEmpty())
                    <ul class="mb-2 list-disc space-y-0.5 pl-5">
                        @foreach ($lab->tests as $test)
                            <li>{{ $test }}</li>
                        @endforeach
                    </ul>
                @endif

                <p class="text-gray-500 dark:text-gray-400">
                    No. Order: {{ $lab->noorder }}
                    @if ($lab->nm_dokter)
                        &middot; {{ $lab->nm_dokter }}
                    @endif
                </p>
                @if ($lab->diagnosa_klinis && $lab->diagnosa_klinis !== '-')
                    <p class="text-gray-500 dark:text-gray-400">Diagnosa klinis: {{ $lab->diagnosa_klinis }}</p>
                @endif
            </a>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat pemeriksaan lab.</p>
        @endforelse
    </div>
@endsection
