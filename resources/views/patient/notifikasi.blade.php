@extends('layouts.patient-app', ['title' => 'Notifikasi'])

@section('content')
    <div class="relative flex items-center gap-3 overflow-hidden bg-blue-600 px-5 py-4 text-white dark:bg-blue-800">
        <div class="relative z-10 flex items-center gap-3">
            <a href="{{ route('patient.dashboard') }}" class="flex size-9 shrink-0 items-center justify-center rounded-full border border-white/50 bg-white/10 transition hover:bg-white/25">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h1 class="text-lg font-semibold">Notifikasi</h1>
        </div>
    </div>

    <div class="space-y-3 px-5 py-4">
        @forelse ($notifications as $notification)
            @php $isNew = $readAt === null || $notification['at']->gt($readAt); @endphp
            <div class="flex gap-3 rounded-2xl bg-white p-4 shadow-sm dark:bg-gray-900 {{ $isNew ? 'ring-1 ring-blue-300 dark:ring-blue-700' : '' }}">
                <span class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full {{ in_array($notification['type'], ['called', 'lab'], true) ? 'bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-300' : 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300' }}">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <p class="text-sm font-semibold">{{ $notification['title'] }}</p>
                        @if ($isNew)
                            <span class="mt-1 size-2 shrink-0 rounded-full bg-blue-600 dark:bg-blue-400"></span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $notification['message'] }}</p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ $notification['at']->translatedFormat('d M Y, H:i') }}</p>
                </div>
            </div>
        @empty
            <p class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada notifikasi.</p>
        @endforelse
    </div>
@endsection
