@extends('layouts.patient-app', ['title' => 'Kamar Tersedia'])

@section('content')
    <div class="relative flex items-center gap-3 overflow-hidden bg-blue-600 px-5 py-4 text-white dark:bg-blue-800">
        <div class="relative z-10 flex items-center gap-3">
            <a href="{{ route('patient.dashboard') }}" class="flex size-9 shrink-0 items-center justify-center rounded-full border border-white/50 bg-white/10 transition hover:bg-white/25">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h1 class="text-lg font-semibold">Kamar Tersedia</h1>
        </div>
    </div>

    <div class="px-5 py-4">
        @if ($kelas->isNotEmpty())
            <div class="mb-4 flex items-center gap-3 rounded-xl bg-blue-50 p-4 dark:bg-blue-900/20">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                </span>
                <p class="text-xs text-gray-600 dark:text-gray-300">
                    Total <span class="font-semibold text-green-600 dark:text-green-400">{{ $kelas->sum('tersedia') }} tempat tidur tersedia</span>
                    dari {{ $kelas->sum('total') }} tempat tidur di semua kelas.
                </p>
            </div>
        @endif

        @forelse ($kelas as $row)
            @php
                $penuh = $row->tersedia <= 0;
                $terisi = $row->total > 0 ? round((($row->total - $row->tersedia) / $row->total) * 100) : 0;
            @endphp
            <div class="mb-3 rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-full {{ $penuh ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400' }}">
                        <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 18.75V6.75m0 8.25h18m0 3.75v-6a3 3 0 0 0-3-3h-7.5v6M6.75 11.25a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold">{{ $row->kelas }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">dari {{ $row->total }} tempat tidur</p>
                    </div>
                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-3 py-1 text-sm font-semibold {{ $penuh ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' }}">
                        @if ($penuh)
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            Penuh
                        @else
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            {{ $row->tersedia }} tersedia
                        @endif
                    </span>
                </div>
                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div class="h-full rounded-full {{ $penuh ? 'bg-red-500' : ($terisi >= 80 ? 'bg-amber-500' : 'bg-green-500') }}" style="width: {{ $terisi }}%"></div>
                </div>
                <p class="mt-1 text-right text-[11px] text-gray-400 dark:text-gray-500">{{ $terisi }}% terisi</p>
            </div>
        @empty
            <div class="flex flex-col items-center gap-2 py-10 text-gray-400 dark:text-gray-500">
                <svg class="size-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 18.75V6.75m0 8.25h18m0 3.75v-6a3 3 0 0 0-3-3h-7.5v6M6.75 11.25a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
                </svg>
                <p class="text-sm">Data kamar belum tersedia.</p>
            </div>
        @endforelse
    </div>
@endsection
