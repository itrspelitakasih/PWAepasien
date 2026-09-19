@extends('layouts.patient-app', ['title' => 'Riwayat Radiologi'])

@section('content')
    <div class="relative flex items-center gap-3 overflow-hidden bg-blue-600 px-5 py-4 text-white dark:bg-blue-800">
        <div class="relative z-10 flex items-center gap-3">
            <a href="{{ route('patient.dashboard') }}" class="flex size-9 shrink-0 items-center justify-center rounded-full border border-white/50 bg-white/10 transition hover:bg-white/25">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h1 class="text-lg font-semibold">Riwayat Radiologi</h1>
        </div>
    </div>

    <div class="px-5 py-4">
        @forelse ($exams as $exam)
            <a
                href="{{ route('patient.radiologi.show', ['no_rawat' => $exam->no_rawat, 'tgl' => $exam->tgl_periksa, 'jam' => $exam->jam]) }}"
                class="mb-3 block rounded-xl border border-gray-200 p-4 text-sm hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-900"
            >
                <div class="mb-1 flex items-center justify-between">
                    <span class="font-semibold">{{ $exam->examined_at->translatedFormat('d F Y') }}</span>
                    <span class="text-gray-500 dark:text-gray-400">{{ $exam->examined_at->format('H:i') }}</span>
                </div>

                @if ($exam->tests->isNotEmpty())
                    <ul class="mb-2 list-disc space-y-0.5 pl-5">
                        @foreach ($exam->tests as $test)
                            <li>{{ $test }}</li>
                        @endforeach
                    </ul>
                @endif

                @if ($exam->nm_dokter)
                    <p class="text-gray-500 dark:text-gray-400">Dokter perujuk: {{ $exam->nm_dokter }}</p>
                @endif
            </a>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada riwayat pemeriksaan radiologi.</p>
        @endforelse
    </div>
@endsection
