@extends('layouts.patient-app', ['title' => 'Pendaftaran Online'])

@section('content')
    <div class="relative flex items-center gap-3 overflow-hidden bg-blue-600 px-5 py-4 text-white dark:bg-blue-800">
        <div class="relative z-10 flex items-center gap-3">
            <a href="{{ route('patient.dashboard') }}" class="rounded-full p-1 hover:bg-white/10">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h1 class="text-lg font-semibold">Pendaftaran Online</h1>
        </div>
    </div>

    <div class="px-5 py-4">
        <form method="GET" action="{{ route('patient.pendaftaran') }}" class="mb-6 flex flex-wrap gap-2">
            <select name="kd_poli" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800">
                <option value="">Pilih Poli</option>
                @foreach ($polis as $poli)
                    <option value="{{ $poli->kd_poli }}" @selected($kdPoli === $poli->kd_poli)>{{ $poli->nm_poli }}</option>
                @endforeach
            </select>
            <input
                type="date"
                name="tanggal"
                value="{{ $tanggal->toDateString() }}"
                min="{{ now()->toDateString() }}"
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
            >
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                Cari Jadwal
            </button>
        </form>

        @if ($kdPoli)
            @if ($jadwal->isEmpty())
                <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">Tidak ada jadwal praktik yang tersedia pada tanggal tersebut.</p>
            @else
                <form method="POST" action="{{ route('patient.pendaftaran.store') }}" class="mb-8 space-y-3">
                    @csrf
                    <input type="hidden" name="kd_poli" value="{{ $kdPoli }}">
                    <input type="hidden" name="tanggal" value="{{ $tanggal->toDateString() }}">

                    @foreach ($jadwal as $entry)
                        <label class="flex items-center justify-between rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800">
                            <span class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    name="kd_dokter"
                                    value="{{ $entry['jadwal']->kd_dokter }}"
                                    required
                                    {{ $entry['sisa_kuota'] <= 0 ? 'disabled' : '' }}
                                    class="text-blue-600 focus:ring-blue-500"
                                >
                                <span>
                                    <span class="block font-medium">{{ $dokters[$entry['jadwal']->kd_dokter]?->nm_dokter ?? $entry['jadwal']->kd_dokter }}</span>
                                    <span class="block text-gray-500 dark:text-gray-400">
                                        {{ \Illuminate\Support\Carbon::parse($entry['jadwal']->jam_mulai)->format('H:i') }}
                                        &ndash;
                                        {{ \Illuminate\Support\Carbon::parse($entry['jadwal']->jam_selesai)->format('H:i') }}
                                    </span>
                                </span>
                            </span>
                            <span class="{{ $entry['sisa_kuota'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                Sisa kuota: {{ $entry['sisa_kuota'] }}
                            </span>
                        </label>
                    @endforeach

                    @if ($penjabs->isNotEmpty())
                        <div>
                            <label for="kd_pj" class="mb-1 block text-sm font-medium">Penanggung Jawab Pembayaran</label>
                            <select
                                id="kd_pj"
                                name="kd_pj"
                                required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800"
                            >
                                <option value="">Pilih penanggung jawab</option>
                                @foreach ($penjabs as $penjab)
                                    <option value="{{ $penjab->kd_pj }}">{{ $penjab->png_jawab }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Daftar
                    </button>
                </form>
            @endif
        @endif

        <section class="pb-4">
            <h2 class="mb-3 text-sm font-semibold text-gray-500 dark:text-gray-400">Pendaftaran Anda</h2>

            @forelse ($bookings as $booking)
                <div class="mb-2 rounded-xl border border-gray-200 p-3 text-sm dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="font-medium">{{ $booking->tanggal_periksa->translatedFormat('d F Y') }}</span>
                        <span class="text-gray-500 dark:text-gray-400">{{ $booking->status }}</span>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">{{ $booking->kd_poli }} &middot; {{ $booking->kd_dokter }}</p>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada pendaftaran online.</p>
            @endforelse
        </section>
    </div>
@endsection
