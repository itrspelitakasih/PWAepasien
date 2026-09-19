@extends('layouts.patient-app', ['title' => 'Hasil Radiologi'])

@section('content')
    <div class="relative flex items-center gap-3 overflow-hidden bg-blue-600 px-5 py-4 text-white dark:bg-blue-800">
        <div class="relative z-10 flex items-center gap-3">
            <a href="{{ route('patient.radiologi') }}" class="flex size-9 shrink-0 items-center justify-center rounded-full border border-white/50 bg-white/10 transition hover:bg-white/25">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h1 class="text-lg font-semibold">Hasil Radiologi</h1>
        </div>
    </div>

    <div class="space-y-4 px-5 py-4">
        <div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800">
            <div class="mb-1 flex items-center justify-between">
                <span class="font-semibold">{{ $exam->examined_at->translatedFormat('d F Y') }}</span>
                <span class="text-gray-500 dark:text-gray-400">{{ $exam->examined_at->format('H:i') }}</span>
            </div>

            @if ($exam->tests->isNotEmpty())
                <ul class="my-2 list-disc space-y-0.5 pl-5">
                    @foreach ($exam->tests as $test)
                        <li>{{ $test }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($exam->nm_dokter)
                <p class="text-gray-500 dark:text-gray-400">Dokter perujuk: {{ $exam->nm_dokter }}</p>
            @endif
        </div>

        <div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800">
            <p class="mb-2 font-semibold">Bacaan</p>
            @if ($bacaan)
                <p class="whitespace-pre-line">{{ $bacaan }}</p>
            @else
                <p class="text-gray-500 dark:text-gray-400">Bacaan hasil belum tersedia.</p>
            @endif
        </div>

        @if ($gambar->isNotEmpty())
            <div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800">
                <p class="mb-2 font-semibold">Gambar</p>
                <div class="space-y-3">
                    @foreach ($gambar as $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener">
                            <img src="{{ $url }}" alt="Gambar radiologi" loading="lazy" class="w-full rounded-lg border border-gray-200 dark:border-gray-800">
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
    </div>
@endsection
