@extends('layouts.patient-app', ['title' => 'Antrean Saya'])

@section('content')
    <div class="relative flex items-center gap-3 overflow-hidden bg-blue-600 px-5 py-4 text-white dark:bg-blue-800">
        <div class="relative z-10 flex items-center gap-3">
            <a href="{{ route('patient.dashboard') }}" class="rounded-full p-1 hover:bg-white/10">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
            <h1 class="text-lg font-semibold">Antrean Saya</h1>
        </div>
    </div>

    <div class="px-5 py-4">
        <livewire:patient.queue-board />
    </div>
@endsection
