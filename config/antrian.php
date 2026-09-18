<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Estimasi Waktu Tunggu
    |--------------------------------------------------------------------------
    |
    | Rata-rata lama pemeriksaan per pasien (menit), dipakai untuk menghitung
    | perkiraan waktu tunggu pasien berikutnya secara sederhana
    | (menit = avg_service_minutes x jumlah pasien di depan).
    |
    */
    'avg_service_minutes' => (int) env('ANTRIAN_AVG_SERVICE_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Ambang "Hampir Giliran"
    |--------------------------------------------------------------------------
    |
    | Jumlah pasien di depan yang memicu notifikasi "hampir giliran".
    |
    */
    'near_threshold' => (int) env('ANTRIAN_NEAR_THRESHOLD', 3),

    /*
    |--------------------------------------------------------------------------
    | Interval Polling Registrasi Khanza
    |--------------------------------------------------------------------------
    |
    | Seberapa sering `antrian:sync` dijadwalkan (detik). Frekuensi di bawah
    | satu menit membutuhkan `php artisan schedule:work` (atau proses
    | setara) yang berjalan terus-menerus, karena cron OS hanya mencentang
    | penjadwal setiap satu menit.
    |
    */
    'poll_interval_seconds' => (int) env('ANTRIAN_POLL_INTERVAL_SECONDS', 30),

    /*
    |--------------------------------------------------------------------------
    | Token API Panggil Antrean
    |--------------------------------------------------------------------------
    |
    | Bearer token yang wajib dikirim aplikasi kasir SIMRS Khanza saat
    | memanggil POST /api/antrian/panggil (dipicu dari tombol "Masuk Poli").
    | Kosong berarti endpoint ini menolak semua permintaan.
    |
    */
    'api_token' => env('ANTRIAN_API_TOKEN'),

];
