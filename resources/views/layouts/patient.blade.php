<!DOCTYPE html>
<html lang="id" data-theme-pref="{{ optional(auth('pasien')->user())->theme ?? 'system' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
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
        @include('partials.patient-theme')
    </head>
    <body class="min-h-screen bg-linear-to-b from-blue-50 via-gray-50 to-white text-gray-900 antialiased dark:from-gray-950 dark:via-gray-950 dark:to-gray-900 dark:text-gray-100">
        @include('partials.patient-backdrop')

        <div class="relative z-10 flex min-h-screen flex-col items-center px-4 py-10">
            @if ($centerLogo ?? false)
                <div class="mb-6 flex w-full max-w-2xl justify-center">
                    @if (\App\Models\Setting::current()->logoUrl())
                        <img
                            src="{{ \App\Models\Setting::current()->logoUrl() }}"
                            alt="{{ \App\Models\Setting::current()->app_name }}"
                            class="h-12 w-auto"
                        >
                    @else
                        <span class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                            {{ \App\Models\Setting::current()->app_name }}
                        </span>
                    @endif
                </div>
            @else
                <div class="mb-6 flex w-full max-w-2xl items-center justify-between">
                    <span class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                        {{ \App\Models\Setting::current()->app_name }}
                    </span>

                    @auth('pasien')
                        <form method="POST" action="{{ route('patient.logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400">
                                Keluar
                            </button>
                        </form>
                    @endauth
                </div>
            @endif

            <div class="w-full {{ $wide ?? false ? 'max-w-2xl' : 'max-w-sm' }} rounded-3xl border border-white/70 bg-white/95 p-7 shadow-[0_24px_60px_-15px_rgba(37,99,235,0.28),0_10px_24px_-10px_rgba(15,23,42,0.18)] ring-1 ring-black/5 backdrop-blur-md dark:border-white/5 dark:bg-gray-900/90 dark:shadow-[0_24px_60px_-15px_rgba(0,0,0,0.8),0_10px_24px_-10px_rgba(0,0,0,0.6)] dark:ring-white/5">
                @if (session('status'))
                    <div class="mb-4 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-300">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </body>
</html>
