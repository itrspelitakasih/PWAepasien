@extends('layouts.patient-app', ['title' => 'Hasil Lab'])

@section('content')
    <div class="relative flex items-center gap-3 overflow-hidden bg-blue-600 px-5 py-4 text-white dark:bg-blue-800">
        <div class="relative z-10 flex items-center gap-3">
            <a href="{{ route('patient.lab') }}" class="flex size-9 shrink-0 items-center justify-center rounded-full border border-white/50 bg-white/10 transition hover:bg-white/25">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h1 class="text-lg font-semibold">Hasil Lab</h1>
        </div>
    </div>

    <div class="space-y-4 px-5 py-4">
        <div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800">
            <div class="mb-1 flex items-center justify-between">
                <span class="font-semibold">{{ $lab->resulted_at->translatedFormat('d F Y') }}</span>
                <span class="text-gray-500 dark:text-gray-400">{{ $lab->resulted_at->format('H:i') }}</span>
            </div>
            <p class="text-gray-500 dark:text-gray-400">
                No. Order: {{ $lab->noorder }}
                @if ($lab->nm_dokter)
                    &middot; {{ $lab->nm_dokter }}
                @endif
            </p>
            @if ($lab->diagnosa_klinis && $lab->diagnosa_klinis !== '-')
                <p class="text-gray-500 dark:text-gray-400">Diagnosa klinis: {{ $lab->diagnosa_klinis }}</p>
            @endif
        </div>

        @forelse ($hasil as $pemeriksaan => $items)
            <div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800">
                <p class="mb-3 font-semibold">{{ $pemeriksaan }}</p>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($items as $item)
                        <div class="flex items-start justify-between gap-3 py-2">
                            <div class="min-w-0">
                                <p>{{ $item->pemeriksaan }}</p>
                                @if ($item->nilai_rujukan)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Rujukan: {{ $item->nilai_rujukan }}</p>
                                @endif
                                @if ($item->keterangan)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $item->keterangan }}</p>
                                @endif
                            </div>
                            <p class="shrink-0 text-right font-medium">
                                @if (trim($item->nilai) !== '')
                                    {{ $item->nilai }} {{ $item->satuan }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Rincian hasil belum tersedia.</p>
        @endforelse
    </div>
@endsection
