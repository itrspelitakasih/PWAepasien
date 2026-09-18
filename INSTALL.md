# Panduan Instalasi — Portal Pasien RSPK

Catatan langkah instalasi dari development lokal sampai production untuk aplikasi Laravel PWA "Portal Pasien" ini, termasuk integrasi SIMRS Khanza (koneksi `sik`, read-only) dan GOWA (WhatsApp gateway) untuk OTP/notifikasi.

> ⚠️ Semua kredensial database/API di bawah adalah **contoh/placeholder**. Jangan pakai nilai asli production di dokumen ini — isi nilai sebenarnya langsung di file `.env` server masing-masing, dan jangan commit `.env` ke git.

## 1. Persyaratan Sistem

- PHP 8.2+ dengan ekstensi: `pdo_mysql`, `mbstring`, `bcmath`, `intl`, `curl`, `fileinfo`, `openssl`
- Composer 2.x
- Node.js 18+ dan npm (untuk build asset Vite)
- MySQL/MariaDB (untuk DB aplikasi `epasien` dan akses read-only ke DB SIMRS Khanza `sik2023_server`)
- Web server (Nginx/Apache) + PHP-FPM untuk production
- Supervisor (atau setara) untuk menjaga queue worker & scheduler tetap berjalan
- Akses jaringan ke server GOWA (WhatsApp gateway) untuk fitur OTP & notifikasi antrean

## 2. Clone & Install Dependency

```bash
git clone <url-repo> PWAepasien
cd PWAepasien

composer install --no-dev --optimize-autoloader
npm install
npm run build
```

Untuk development, gunakan `composer install` tanpa `--no-dev` dan `npm run dev`.

## 3. Konfigurasi Environment (`.env`)

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` dan isi bagian berikut sesuai lingkungan masing-masing:

```env
APP_NAME="Portal Pasien RSPK"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://epasien.contoh-domain.id

APP_LOCALE=id
APP_FALLBACK_LOCALE=en

# Database aplikasi (bukan Khanza) — ganti dengan kredensial DB Anda sendiri
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=epasien
DB_USERNAME=epasien_user
DB_PASSWORD=ganti_dengan_password_kuat

# Koneksi SIMRS Khanza (read-only) — ganti dengan kredensial DB Khanza Anda sendiri
SIK_DB_HOST=127.0.0.1
SIK_DB_PORT=3306
SIK_DB_DATABASE=sik
SIK_DB_USERNAME=sik_readonly_user
SIK_DB_PASSWORD=ganti_dengan_password_kuat

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# GOWA WhatsApp Gateway
GOWA_BASE_URL=https://gowa.contoh-domain.id/
GOWA_USERNAME=admin
GOWA_PASSWORD=ganti_dengan_password_gowa
GOWA_TIMEOUT=15
GOWA_WEBHOOK_SECRET=isi_secret_acak_jika_pakai_webhook
GOWA_WEBHOOK_PATH=webhooks/gowa

# Token dipanggil dari SIMRS Khanza untuk POST /api/antrian/panggil
# (lihat wa.AntrianApiService & ANTRIAN_API_TOKEN di setting/database-extra.xml pada Khanza)
ANTRIAN_API_TOKEN=generate_token_acak_yang_panjang

# Opsional: tuning antrean
ANTRIAN_AVG_SERVICE_MINUTES=10
ANTRIAN_NEAR_THRESHOLD=3
ANTRIAN_POLL_INTERVAL_SECONDS=30
```

Catatan penting:

- Koneksi `sik` **read-only** secara logika aplikasi (lihat `app/Models/Khanza/Concerns/ReadOnlyFromKhanza`), tapi tetap disarankan pakai _database user_ MySQL dengan hak `SELECT` saja (kecuali tabel `booking_registrasi` & `booking_periksa` yang memang sengaja ditulis aplikasi ini — beri hak `INSERT` khusus untuk dua tabel itu jika ingin membatasi lebih ketat).
- Nilai `SIK_DB_*` dan `GOWA_*` di `.env` bisa di-override lewat halaman **Settings** di panel admin Filament tanpa redeploy (lihat `App\Providers\RuntimeSettingsServiceProvider`) — field yang dikosongkan di Settings akan tetap memakai nilai `.env`.
- Generate `ANTRIAN_API_TOKEN` dengan nilai acak & panjang, misalnya `php artisan tinker --execute="echo bin2hex(random_bytes(32));"`.

## 4. Setup Database & Storage

```bash
php artisan migrate --force
php artisan storage:link
```

Jika perlu data awal (misalnya admin Filament pertama), buat lewat seeder atau `php artisan make:filament-user`.

## 5. Build Asset Frontend

```bash
npm run build
```

Hasil build masuk ke `public/build` — pastikan direktori ini ikut ter-deploy ke server production.

## 6. Cache Konfigurasi (Production)

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> Jika ada perubahan di `.env` setelah `config:cache`, jalankan ulang `php artisan config:clear` lalu `config:cache` — konfigurasi tidak lagi dibaca langsung dari `.env` setelah di-cache.

## 7. Queue Worker & Scheduler

Aplikasi ini punya dua proses background yang wajib jalan terus-menerus di production:

### 7a. Queue Worker

Dipakai untuk job antrean/notifikasi WhatsApp (GOWA).

```bash
php artisan queue:work --tries=3 --sleep=3
```

### 7b. Scheduler — `antrian:sync`

`routes/console.php` menjadwalkan `antrian:sync` setiap 30 detik (`everyThirtySeconds()`), untuk polling registrasi baru dari SIMRS Khanza. Karena interval di bawah 1 menit, **cron OS biasa saja tidak cukup** — wajib pakai proses yang selalu hidup:

```bash
php artisan schedule:work
```

### 7c. Jalankan dengan Supervisor (disarankan untuk production)

Contoh config Supervisor (`/etc/supervisor/conf.d/epasien.conf`):

```ini
[program:epasien-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/PWAepasien/artisan queue:work --tries=3 --sleep=3
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/PWAepasien/storage/logs/queue-worker.log
stopwaitsecs=3600

[program:epasien-scheduler]
process_name=%(program_name)s
command=php /path/to/PWAepasien/artisan schedule:work
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/PWAepasien/storage/logs/scheduler.log
```

Lalu:

```bash
supervisorctl reread
supervisorctl update
supervisorctl start epasien-queue:* epasien-scheduler
```

## 8. Web Server (Nginx contoh)

```nginx
server {
    listen 80;
    server_name epasien.contoh-domain.id;
    root /path/to/PWAepasien/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Untuk HTTPS, gunakan Certbot/Let's Encrypt lalu redirect port 80 ke 443 — ini wajib karena PWA (service worker, install prompt) hanya aktif di HTTPS atau `localhost`.

## 9. Permission File

```bash
chown -R www-data:www-data /path/to/PWAepasien
chmod -R 775 storage bootstrap/cache
```

## 10. Checklist Sebelum Go-Live

- [ ] `APP_ENV=production` dan `APP_DEBUG=false`
- [ ] `APP_URL` sudah pakai domain HTTPS yang benar (dipakai untuk manifest PWA & link OTP WhatsApp)
- [ ] DB user untuk koneksi `sik` sudah dibatasi hak aksesnya (idealnya `SELECT` saja + `INSERT` pada `booking_registrasi` & `booking_periksa`)
- [ ] `ANTRIAN_API_TOKEN` sudah diset dan sudah disinkronkan ke setting `ANTRIAN_API_TOKEN` di `setting/database-extra.xml` SIMRS Khanza agar endpoint `POST /api/antrian/panggil` bisa dipanggil dari Khanza
- [ ] GOWA sudah terhubung & device WhatsApp aktif (cek dari panel admin Filament, plugin `gowa-php/filament`)
- [ ] `queue:work` dan `schedule:work` berjalan lewat Supervisor (bukan proses manual di terminal)
- [ ] `php artisan config:cache`, `route:cache`, `view:cache` sudah dijalankan ulang setiap kali `.env` atau route berubah
- [ ] Backup rutin untuk DB `epasien` (DB Khanza `sik` sudah punya backup terpisah dari SIMRS)
- [ ] Sertifikat SSL aktif dan auto-renew (Certbot timer/cron)

## 11. Update / Deploy Ulang

```bash
git pull
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
supervisorctl restart epasien-queue:* epasien-scheduler
```
