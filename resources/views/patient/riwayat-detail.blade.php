@extends('layouts.patient-app', ['title' => 'Riwayat Perawatan'])

@section('content')
    <div class="relative flex items-center gap-3 overflow-hidden bg-blue-600 px-5 py-4 text-white dark:bg-blue-800">
        <div class="relative z-10 flex items-center gap-3">
            <a href="{{ route('patient.riwayat') }}" class="rounded-full p-1 hover:bg-white/10">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h1 class="text-lg font-semibold">Riwayat Perawatan</h1>
        </div>
    </div>

    <div class="space-y-4 px-5 py-4">
        <div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800">
            <div class="mb-1 flex items-center justify-between">
                <span class="font-semibold">{{ $visit->tgl_registrasi->translatedFormat('d F Y') }}</span>
                <span class="text-gray-500 dark:text-gray-400">{{ $visit->stts }}</span>
            </div>
            <p class="text-gray-500 dark:text-gray-400">
                {{ $poli?->nm_poli ?? $visit->kd_poli }}
                &middot;
                {{ $dokter?->nm_dokter ?? $visit->kd_dokter }}
            </p>
        </div>

        @forelse ($pemeriksaan as $catatan)
            <div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800">
                <div class="mb-3 flex items-center justify-between">
                    <span class="font-semibold">Catatan Perawatan</span>
                    <span class="text-gray-500 dark:text-gray-400">
                        {{ \Illuminate\Support\Carbon::parse($catatan->tgl_perawatan)->translatedFormat('d F Y') }}
                        {{ \Illuminate\Support\Carbon::parse($catatan->jam_rawat)->format('H:i') }}
                    </span>
                </div>

                <div class="mb-3 grid grid-cols-3 gap-2 text-center">
                    @foreach ([
                        'Tensi' => $catatan->tensi,
                        'Nadi' => $catatan->nadi ? "{$catatan->nadi} x/mnt" : null,
                        'Suhu' => $catatan->suhu_tubuh ? "{$catatan->suhu_tubuh}°C" : null,
                        'Respirasi' => $catatan->respirasi ? "{$catatan->respirasi} x/mnt" : null,
                        'SpO2' => $catatan->spo2 ? "{$catatan->spo2}%" : null,
                        'Kesadaran' => $catatan->kesadaran,
                    ] as $label => $value)
                        @if ($value)
                            <div class="rounded-lg bg-gray-50 p-2 dark:bg-gray-900">
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $label }}</p>
                                <p class="font-medium">{{ $value }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>

                @foreach ([
                    'Keluhan' => $catatan->keluhan,
                    'Hasil Pemeriksaan' => $catatan->pemeriksaan,
                    'Penilaian' => $catatan->penilaian,
                    'Rencana Tindak Lanjut' => $catatan->rtl,
                    'Instruksi' => $catatan->instruksi,
                    'Evaluasi' => $catatan->evaluasi,
                ] as $label => $value)
                    @if (trim((string) $value) !== '')
                        <div class="mb-2">
                            <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                            <p>{{ $value }}</p>
                        </div>
                    @endif
                @endforeach

                @if ($catatan->nama_petugas)
                    <p class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">Petugas: {{ $catatan->nama_petugas }}</p>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-500 dark:text-gray-400">Belum ada catatan perawatan untuk kunjungan ini.</p>
        @endforelse

        @if ($diagnosa->isNotEmpty())
            <div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800">
                <p class="mb-2 font-semibold">Diagnosa</p>
                <ul class="list-inside list-disc space-y-1">
                    @foreach ($diagnosa as $item)
                        <li>
                            {{ $item->nm_penyakit }}
                            @if ($item->status_penyakit)
                                <span class="text-gray-500 dark:text-gray-400">({{ $item->status_penyakit }})</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($resep->isNotEmpty())
            <div class="rounded-xl border border-gray-200 p-4 text-sm dark:border-gray-800">
                <p class="mb-2 font-semibold">Resep Obat</p>
                <ul class="space-y-1">
                    @foreach ($resep as $item)
                        <li class="flex items-center justify-between">
                            <span>{{ $item->nama_brng }}</span>
                            <span class="text-gray-500 dark:text-gray-400">{{ (int) $item->jml }}</span>
                        </li>
                        @if ($item->aturan_pakai)
                            <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ $item->aturan_pakai }}</p>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
