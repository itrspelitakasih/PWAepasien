<div wire:poll.10s>
    @forelse ($tickets as $entry)
        @php $ticket = $entry['ticket']; @endphp
        <div class="mb-4 rounded-lg border border-gray-200 p-4 dark:border-gray-800">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $ticket->queue_number }}</span>
                <span class="rounded-full px-3 py-1 text-xs font-semibold
                    {{ $ticket->status === 'called' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                    {{ $ticket->status === 'called' ? 'Sedang Dipanggil' : 'Menunggu' }}
                </span>
            </div>

            <dl class="grid grid-cols-2 gap-2 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Poli</dt>
                    <dd class="font-medium">{{ $ticket->kd_poli }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Tanggal</dt>
                    <dd class="font-medium">{{ $ticket->tanggal->translatedFormat('d F Y') }}</dd>
                </div>
                @if ($ticket->status === 'waiting')
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Pasien di depan Anda</dt>
                        <dd class="font-medium">{{ $entry['position_ahead'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Perkiraan waktu tunggu</dt>
                        <dd class="font-medium">&plusmn; {{ $entry['eta_minutes'] }} menit</dd>
                    </div>
                @endif
            </dl>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada antrean aktif saat ini.</p>
    @endforelse
</div>
