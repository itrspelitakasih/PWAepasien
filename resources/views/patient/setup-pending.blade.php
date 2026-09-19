<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Portal Belum Siap &mdash; {{ \App\Models\Setting::current()->app_name }}</title>
        <script>
            (function () {
                try {
                    var pref = localStorage.getItem('theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                    document.documentElement.classList.toggle('dark', pref === 'dark');
                } catch (e) {}
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.patient-theme')
    </head>
    <body class="min-h-screen bg-gray-50 text-gray-900 antialiased dark:bg-gray-950 dark:text-gray-100">
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10 text-center">
            <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-blue-50 text-blue-500 dark:bg-blue-900/30 dark:text-blue-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                </svg>
            </div>

            <h1 class="mb-2 text-2xl font-semibold">Portal Sedang Dalam Pengaturan</h1>
            <p class="max-w-md text-sm text-gray-500 dark:text-gray-400">
                Portal pasien belum bisa diakses karena masih menunggu konfigurasi dari admin.
                Silakan coba lagi beberapa saat lagi.
            </p>

            <button
                type="button"
                onclick="window.location.reload()"
                class="mt-6 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
            >
                Coba Lagi
            </button>

            <p class="mt-8 text-xs text-gray-400 dark:text-gray-600">
                {{ \App\Models\Setting::current()->app_name }}
            </p>
        </div>
    </body>
</html>
