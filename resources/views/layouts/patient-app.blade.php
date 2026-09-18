<!DOCTYPE html>
<html lang="id" data-theme-pref="{{ optional(auth('pasien')->user())->theme ?? 'system' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title ?? 'Portal Pasien' }} &mdash; {{ \App\Models\Setting::current()->app_name }}</title>
        <script>
            (function () {
                try {
                    var pref = document.documentElement.dataset.themePref;
                    if (pref !== 'dark' && pref !== 'light') {
                        pref = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    }
                    document.documentElement.classList.toggle('dark', pref === 'dark');
                    localStorage.setItem('theme', pref);
                } catch (e) {}
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-gray-200 text-gray-900 antialiased dark:bg-black dark:text-gray-100">
        <div class="relative mx-auto flex min-h-screen w-full max-w-md flex-col bg-gray-50 shadow-xl dark:bg-gray-950">
            <div class="flex-1">
                @if (session('status'))
                    <div class="mx-4 mt-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-300">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mx-4 mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>

            <nav class="sticky bottom-0 flex items-center justify-between border-t border-gray-200 bg-white px-6 pb-[calc(env(safe-area-inset-bottom)+0.5rem)] pt-2 dark:border-gray-800 dark:bg-gray-900">
                <a href="{{ route('patient.dashboard') }}" class="flex flex-col items-center gap-1 px-2 py-1 text-xs font-medium {{ request()->routeIs('patient.dashboard') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500' }}">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955a1.5 1.5 0 0 1 2.122 0L22.28 12M4.5 9.75v9a.75.75 0 0 0 .75.75H9v-4.5a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5V19.5h3.75a.75.75 0 0 0 .75-.75v-9" />
                    </svg>
                    Home
                </a>

                <a href="{{ route('patient.jadwal') }}" class="flex flex-col items-center gap-1 px-2 py-1 text-xs font-medium {{ request()->routeIs('patient.jadwal') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500' }}">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 18.75h16.5A.75.75 0 0 0 21 18V6.75a.75.75 0 0 0-.75-.75H3.75a.75.75 0 0 0-.75.75V18c0 .414.336.75.75.75Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5h18" />
                    </svg>
                    Booking
                </a>

                <a
                    href="{{ route('patient.pendaftaran') }}"
                    class="-mt-8 flex size-14 items-center justify-center rounded-full bg-blue-600 text-white shadow-lg ring-4 ring-gray-50 hover:bg-blue-700 dark:ring-gray-950"
                >
                    <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25v3m1.5-1.5h-3" />
                    </svg>
                </a>

                <a href="{{ route('patient.riwayat') }}" class="flex flex-col items-center gap-1 px-2 py-1 text-xs font-medium {{ request()->routeIs('patient.riwayat') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500' }}">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Riwayat
                </a>

                <a href="{{ route('patient.settings') }}" class="flex flex-col items-center gap-1 px-2 py-1 text-xs font-medium {{ request()->routeIs('patient.settings') ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500' }}">
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.964 0a9 9 0 1 0-11.964 0m11.964 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    Profil
                </a>
            </nav>
        </div>
    </body>
</html>
