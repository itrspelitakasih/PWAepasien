@extends('layouts.patient-app', ['title' => 'Jadwal Dokter'])

@section('content')
    <div class="relative flex items-center gap-3 overflow-hidden bg-blue-600 px-5 py-4 text-white dark:bg-blue-800">
        <div class="relative z-10 flex items-center gap-3">
            <a href="{{ route('patient.dashboard') }}" class="rounded-full p-1 hover:bg-white/10">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h1 class="text-lg font-semibold">Jadwal Dokter</h1>
        </div>
    </div>

    <form method="GET" action="{{ route('patient.jadwal') }}" class="flex flex-wrap gap-2 px-5 py-4">
        <select name="kd_poli" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
            <option value="">Pilih Poli</option>
            @foreach ($polis as $poli)
                <option value="{{ $poli->kd_poli }}" @selected($kdPoli === $poli->kd_poli)>{{ $poli->nm_poli }}</option>
            @endforeach
        </select>
        <input
            type="date"
            name="tanggal"
            value="{{ $tanggal->toDateString() }}"
            class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
        >
        <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Cari
        </button>
    </form>

    <div class="px-5 pb-6">
        @if (! $kdPoli)
            <p class="text-sm text-gray-500 dark:text-gray-400">Pilih poli untuk melihat jadwal.</p>
        @elseif ($isHoliday)
            <p class="text-sm text-gray-500 dark:text-gray-400">Tanggal yang dipilih adalah hari libur.</p>
        @elseif ($jadwal->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada jadwal praktik pada tanggal tersebut.</p>
        @else
            <div class="space-y-3">
                @foreach ($jadwal as $entry)
                    <div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800">
                        <div class="mb-1 flex items-center justify-between">
                            <span class="font-semibold">{{ $dokters[$entry['jadwal']->kd_dokter]?->nm_dokter ?? $entry['jadwal']->kd_dokter }}</span>
                            <span class="{{ $entry['sisa_kuota'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                Sisa kuota: {{ $entry['sisa_kuota'] }}
                            </span>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ \Illuminate\Support\Carbon::parse($entry['jadwal']->jam_mulai)->format('H:i') }}
                            &ndash;
                            {{ \Illuminate\Support\Carbon::parse($entry['jadwal']->jam_selesai)->format('H:i') }}
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
