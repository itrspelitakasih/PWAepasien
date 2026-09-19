# Portal Pasien (ePasien)

Portal pasien berbasis web (mobile-first) untuk rumah sakit yang memakai **SIMRS Khanza**. Dibangun dengan Laravel 12 + Filament 5 (panel admin) + GOWA (WhatsApp gateway).

Fitur utama:

- Login pasien dengan nomor RM + OTP WhatsApp, serta pendaftaran akun mandiri
- Pendaftaran kunjungan/booking poli langsung ke Khanza
- Antrean poli real-time: tiket, estimasi waktu tunggu, notifikasi "hampir giliran" dan "dipanggil" via WhatsApp
- Riwayat kunjungan, hasil laboratorium (dengan notifikasi saat hasil selesai), hasil radiologi, jadwal dokter, info kamar, dan surat
- Panel admin (`/admin`) untuk pengaturan aplikasi, koneksi Khanza, dan koneksi GOWA tanpa perlu redeploy

## Daftar Isi

1. [Syarat Sistem](#1-syarat-sistem)
2. [Arsitektur Singkat](#2-arsitektur-singkat)
3. [Instalasi Development](#3-instalasi-development)
4. [Instalasi Production](#4-instalasi-production)
5. [Konfigurasi Awal di Panel Admin](#5-konfigurasi-awal-di-panel-admin)
6. [Integrasi dengan SIMRS Khanza](#6-integrasi-dengan-simrs-khanza)
7. [Perintah Artisan & Jadwal Otomatis](#7-perintah-artisan--jadwal-otomatis)
8. [Update / Deploy Ulang](#8-update--deploy-ulang)
9. [Checklist Go-Live](#9-checklist-go-live)
10. [Troubleshooting](#10-troubleshooting)

> Semua nilai pada dokumen ini (domain, host, user, password, token) hanyalah **contoh/placeholder**. Isi dengan nilai lingkungan Anda sendiri langsung di file `.env` server dan jangan pernah meng-commit `.env` ke git.

---

## 1. Syarat Sistem

### Server aplikasi

| Komponen | Kebutuhan |
|---|---|
| OS | Linux (Ubuntu/Debian disarankan) untuk production. Windows dapat dipakai untuk development |
| PHP | **8.2 atau lebih baru** |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `bcmath`, `intl`, `curl`, `fileinfo`, `openssl`, `xml`, `zip`, `gd` (atau `imagick`) |
| Composer | 2.x |
| Node.js | 18+ beserta npm (hanya untuk build asset Vite) |
| Web server | Nginx atau Apache + PHP-FPM |
| Process manager | Supervisor (atau setara, mis. systemd) untuk queue worker dan scheduler |
| HTTPS | Sertifikat SSL valid (mis. Let's Encrypt) |

### Database

| Komponen | Kebutuhan |
|---|---|
| Database aplikasi | MySQL 8+ / MariaDB 10.6+ — database baru khusus aplikasi ini (mis. `epasien`) |
| Database SIMRS Khanza | Akses jaringan ke server MySQL/MariaDB Khanza. Disarankan memakai user khusus dengan hak terbatas (lihat [bagian 6](#6-integrasi-dengan-simrs-khanza)) |

### Layanan eksternal

| Layanan | Keterangan |
|---|---|
| SIMRS Khanza | Sumber data pasien, registrasi, jadwal, lab, radiologi, dan kamar. Aplikasi ini membaca langsung dari database Khanza |
| GOWA (go-whatsapp-web-multidevice) | Server WhatsApp gateway yang sudah berjalan dan minimal satu device WhatsApp sudah terhubung. Dipakai untuk OTP dan notifikasi |
| Web radiologi Khanza (opsional) | URL folder `webapps/radiologi` Khanza yang dapat diakses dari perangkat pasien, jika ingin menampilkan gambar radiologi |

### Kebutuhan jaringan

- Server aplikasi dapat menjangkau MySQL Khanza (biasanya port 3306) dan server GOWA.
- Server Khanza (aplikasi kasir/pendaftaran) dapat menjangkau server aplikasi ini lewat HTTP(S) agar bisa memanggil endpoint antrean.
- Pasien mengakses aplikasi lewat domain publik HTTPS.

---

## 2. Arsitektur Singkat

```
Perangkat pasien  ──HTTPS──►  Nginx + PHP-FPM  (Laravel: portal /, panel /admin)
                                   │
        ┌──────────────────────────┼─────────────────────────────┐
        ▼                          ▼                             ▼
  DB aplikasi (epasien)     DB Khanza (koneksi "sik")      Server GOWA (WhatsApp)
  akun, OTP, tiket antrean, baca data pasien; tulis        kirim OTP & notifikasi
  setting, sesi, queue      hanya booking_registrasi
                            dan booking_periksa

Proses background (Supervisor):
  queue:work      → mengirim notifikasi WhatsApp
  schedule:work   → antrian:sync, lab:sync (tiap 30 detik), antrian:purge (harian)
```

Portal pasien menampilkan halaman "setup pending" (HTTP 503) sampai koneksi GOWA dan koneksi database Khanza dikonfigurasi dengan benar.

---

## 3. Instalasi Development

```bash
git clone <url-repo> PWAepasien
cd PWAepasien

composer install
cp .env.example .env
php artisan key:generate
```

Untuk development cepat, `.env.example` memakai SQLite. Buat file databasenya lalu migrasi:

```bash
touch database/database.sqlite        # Windows PowerShell: New-Item database/database.sqlite
php artisan migrate
php artisan make:filament-user        # buat akun admin panel
```

Jalankan aplikasi:

```bash
npm install
composer dev
```

`composer dev` menjalankan server Laravel, queue listener, log viewer (pail), dan Vite sekaligus. Portal pasien ada di `http://localhost:8000`, panel admin di `http://localhost:8000/admin`.

Koneksi Khanza dan GOWA tetap perlu diisi (lewat `.env` atau halaman **Pengaturan** di panel admin) agar portal tidak berhenti di halaman "setup pending".

Menjalankan test:

```bash
composer test
```

---

## 4. Instalasi Production

Contoh berikut memakai Ubuntu/Debian, Nginx, PHP 8.2-FPM, dan aplikasi di `/var/www/epasien`. Sesuaikan path, versi PHP, dan domain.

### 4.1 Siapkan server

```bash
sudo apt update
sudo apt install -y nginx mysql-server supervisor git unzip \
    php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring php8.2-bcmath \
    php8.2-intl php8.2-curl php8.2-xml php8.2-zip php8.2-gd
```

Install Composer dan Node.js 18+ sesuai panduan resminya (`getcomposer.org`, `nodejs.org`).

### 4.2 Buat database aplikasi

```sql
CREATE DATABASE epasien CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'epasien_user'@'localhost' IDENTIFIED BY 'ganti_dengan_password_kuat';
GRANT ALL PRIVILEGES ON epasien.* TO 'epasien_user'@'localhost';
FLUSH PRIVILEGES;
```

### 4.3 Ambil kode & install dependency

```bash
cd /var/www
sudo git clone <url-repo> epasien
cd epasien

composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Hasil build berada di `public/build` (tidak ikut git, harus dibuat di server atau di-deploy dari CI).

### 4.4 Konfigurasi `.env`

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_NAME="Portal Pasien"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://epasien.contoh-domain.id

APP_LOCALE=id
APP_FALLBACK_LOCALE=en

LOG_LEVEL=warning

# Database aplikasi
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=epasien
DB_USERNAME=epasien_user
DB_PASSWORD=ganti_dengan_password_kuat

# Koneksi SIMRS Khanza (baca data pasien)
SIK_DB_HOST=192.0.2.10
SIK_DB_PORT=3306
SIK_DB_DATABASE=<nama_database_khanza>
SIK_DB_USERNAME=<user_khanza_terbatas>
SIK_DB_PASSWORD=ganti_dengan_password_kuat

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# GOWA (WhatsApp gateway)
GOWA_BASE_URL=https://gowa.contoh-domain.id/
GOWA_USERNAME=<username_gowa>
GOWA_PASSWORD=ganti_dengan_password_gowa
GOWA_TIMEOUT=15
GOWA_WEBHOOK_SECRET=isi_secret_acak
GOWA_WEBHOOK_PATH=webhooks/gowa

# Token untuk Khanza memanggil POST /api/antrian/panggil
ANTRIAN_API_TOKEN=isi_token_acak_panjang

# Opsional
ANTRIAN_AVG_SERVICE_MINUTES=10
ANTRIAN_NEAR_THRESHOLD=3
RADIOLOGI_IMAGE_BASE_URL=
```

Membuat token acak:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Catatan:

- Nilai `SIK_DB_*`, `GOWA_*`, dan `RADIOLOGI_IMAGE_BASE_URL` dapat di-override dari halaman **Pengaturan** di panel admin tanpa redeploy. Field yang dikosongkan di Pengaturan tetap memakai nilai `.env`.
- Zona waktu aplikasi diatur di `config/app.php` (`timezone`). Sesuaikan dengan lokasi rumah sakit bila berbeda.
- Setiap kali `.env` diubah setelah `config:cache`, jalankan ulang `php artisan config:cache`.

### 4.5 Migrasi database & storage

```bash
php artisan migrate --force
php artisan storage:link
php artisan make:filament-user       # buat akun admin pertama
```

> Semua akun di tabel `users` dapat masuk ke panel admin. Buat akun hanya untuk petugas yang berwenang dan gunakan password kuat.

### 4.6 Permission

```bash
sudo chown -R www-data:www-data /var/www/epasien
sudo chmod -R 775 storage bootstrap/cache
```

### 4.7 Cache konfigurasi

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 4.8 Nginx

`/etc/nginx/sites-available/epasien`:

```nginx
server {
    listen 80;
    server_name epasien.contoh-domain.id;
    root /var/www/epasien/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;
    client_max_body_size 10M;

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

```bash
sudo ln -s /etc/nginx/sites-available/epasien /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

### 4.9 HTTPS

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d epasien.contoh-domain.id
```

Certbot akan menambahkan konfigurasi HTTPS dan pengalihan dari HTTP, serta menjadwalkan perpanjangan otomatis. HTTPS wajib untuk production karena sesi login pasien dan OTP dikirim lewat jaringan publik.

### 4.10 Queue worker & scheduler dengan Supervisor

Aplikasi punya dua proses yang **harus selalu berjalan**:

- **Queue worker** — mengirim notifikasi WhatsApp (OTP, antrean, hasil lab).
- **Scheduler** — menjalankan `antrian:sync` dan `lab:sync` setiap 30 detik. Karena interval di bawah 1 menit, cron OS biasa tidak cukup; gunakan `schedule:work`.

`/etc/supervisor/conf.d/epasien.conf`:

```ini
[program:epasien-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/epasien/artisan queue:work --tries=3 --sleep=3
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/epasien/storage/logs/queue-worker.log
stopwaitsecs=3600

[program:epasien-scheduler]
process_name=%(program_name)s
command=php /var/www/epasien/artisan schedule:work
autostart=true
autorestart=true
numprocs=1
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/epasien/storage/logs/scheduler.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

---

## 5. Konfigurasi Awal di Panel Admin

Buka `https://epasien.contoh-domain.id/admin`, login dengan akun yang dibuat lewat `make:filament-user`, lalu buka halaman **Pengaturan**:

1. **Identitas Aplikasi, Logo & Ikon, Tema Warna** — sesuaikan branding rumah sakit.
2. **WhatsApp (GOWA)** — isi Base URL, username, password, dan (opsional) Default Device ID, lalu klik **Uji Koneksi WhatsApp**.
3. **Koneksi Database SIMRS Khanza** — isi host, port, nama database, username, dan password, lalu klik **Uji Koneksi Database**.
4. **Radiologi** (opsional) — isi URL dasar gambar radiologi Khanza.
5. **Antrean** (opsional) — aktifkan penghapusan otomatis tiket antrean lama dan tentukan berapa hari disimpan.

Pastikan device WhatsApp sudah terhubung di GOWA. Jika perangkat sudah terdaftar di server GOWA tetapi belum muncul di aplikasi, jalankan:

```bash
php artisan gowa:import-devices
```

Setelah GOWA dan koneksi Khanza sama-sama berhasil, portal pasien di `/` akan aktif (bila belum, halaman "setup pending" tetap tampil dan cache status diperbarui dalam ±30 detik).

---

## 6. Integrasi dengan SIMRS Khanza

### 6.1 Hak akses database Khanza

Koneksi `sik` bersifat read-only secara logika aplikasi, kecuali dua tabel yang sengaja ditulis untuk pendaftaran booking pasien: `booking_registrasi` dan `booking_periksa`. Sebaiknya buat user MySQL khusus di server Khanza:

```sql
CREATE USER 'epasien_ro'@'<ip_server_aplikasi>' IDENTIFIED BY 'ganti_dengan_password_kuat';
GRANT SELECT ON <nama_database_khanza>.* TO 'epasien_ro'@'<ip_server_aplikasi>';
GRANT INSERT ON <nama_database_khanza>.booking_registrasi TO 'epasien_ro'@'<ip_server_aplikasi>';
GRANT INSERT ON <nama_database_khanza>.booking_periksa TO 'epasien_ro'@'<ip_server_aplikasi>';
FLUSH PRIVILEGES;
```

Pastikan MySQL Khanza menerima koneksi dari IP server aplikasi (`bind-address` dan firewall).

### 6.2 Sinkronisasi antrean

Perintah `antrian:sync` membaca registrasi rawat jalan baru dari Khanza (`reg_periksa`), menerbitkan tiket antrean, dan menjaga status tiket hari ini tetap sesuai Khanza (dibatalkan bila kunjungan dibatalkan, dipulihkan bila kembali aktif). Perintah ini dijalankan otomatis oleh scheduler.

### 6.3 Memanggil pasien dari Khanza

Endpoint berikut dipanggil dari aplikasi Khanza (mis. tombol "Masuk Poli") untuk memanggil tiket antrean pasien dan memicu notifikasi WhatsApp:

```http
POST https://epasien.contoh-domain.id/api/antrian/panggil
Authorization: Bearer <ANTRIAN_API_TOKEN>
Content-Type: application/json

{ "no_rawat": "2026/01/01/000001" }
```

Respons: `200` bila tiket berhasil dipanggil, `404` bila tiket tidak ditemukan, `401` bila token salah atau `ANTRIAN_API_TOKEN` kosong. Nilai token di sisi Khanza harus sama dengan `ANTRIAN_API_TOKEN` di `.env` aplikasi ini.

### 6.4 Notifikasi hasil laboratorium

`lab:sync` memeriksa `permintaan_lab` Khanza yang selesai hari ini dan mengirim satu notifikasi WhatsApp per permintaan ke pasien terkait.

---

## 7. Perintah Artisan & Jadwal Otomatis

| Perintah | Fungsi | Jadwal |
|---|---|---|
| `antrian:sync` | Ambil registrasi baru dari Khanza, terbitkan dan sinkronkan tiket antrean | Tiap 30 detik |
| `lab:sync` | Kirim notifikasi hasil lab yang sudah selesai | Tiap 30 detik |
| `antrian:purge` | Hapus tiket antrean lama sesuai pengaturan (tidak melakukan apa-apa jika dinonaktifkan; gunakan `--force` untuk memaksa) | Harian 00:05 |
| `gowa:import-devices` | Impor device WhatsApp yang sudah terdaftar di server GOWA | Manual |

Semua jadwal dijalankan oleh `php artisan schedule:work` (lihat [4.10](#410-queue-worker--scheduler-dengan-supervisor)).

---

## 8. Update / Deploy Ulang

```bash
cd /var/www/epasien
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
sudo supervisorctl restart epasien-queue:* epasien-scheduler
php artisan up
```

Queue worker dan scheduler **harus di-restart** setiap deploy agar memakai kode terbaru.

---

## 9. Checklist Go-Live

- [ ] `APP_ENV=production` dan `APP_DEBUG=false`
- [ ] `APP_URL` memakai domain HTTPS yang benar
- [ ] Sertifikat SSL aktif dan perpanjangan otomatis berjalan
- [ ] `php artisan migrate --force` sudah dijalankan tanpa error
- [ ] Akun admin panel dibuat dengan password kuat; tidak ada akun yang tidak dikenal di tabel `users`
- [ ] User database Khanza dibatasi (`SELECT` + `INSERT` pada `booking_registrasi` dan `booking_periksa`)
- [ ] **Uji Koneksi Database** dan **Uji Koneksi WhatsApp** di halaman Pengaturan sama-sama berhasil
- [ ] OTP WhatsApp sampai ke nomor pasien uji
- [ ] `ANTRIAN_API_TOKEN` diisi dan sudah diset yang sama di sisi Khanza; uji `POST /api/antrian/panggil`
- [ ] `queue:work` dan `schedule:work` berjalan lewat Supervisor (`supervisorctl status`)
- [ ] `config:cache`, `route:cache`, `view:cache` sudah dijalankan
- [ ] Permission `storage/` dan `bootstrap/cache/` benar; `php artisan storage:link` sudah dijalankan
- [ ] Backup rutin database aplikasi (database Khanza dibackup terpisah oleh tim SIMRS)
- [ ] File `.env` tidak ter-commit dan tidak dapat diakses lewat web

---

## 10. Troubleshooting

| Gejala | Kemungkinan penyebab & solusi |
|---|---|
| Portal menampilkan "setup pending" (503) | GOWA Base URL kosong atau koneksi database Khanza gagal. Cek halaman Pengaturan, gunakan tombol uji koneksi, lalu tunggu ±30 detik |
| OTP / notifikasi WhatsApp tidak terkirim | Queue worker tidak berjalan (`supervisorctl status`), device GOWA belum terhubung, atau kredensial GOWA salah. Cek `storage/logs/laravel.log` dan `queue-worker.log` |
| Tiket antrean tidak muncul | Scheduler tidak berjalan. Pastikan `schedule:work` aktif dan jalankan `php artisan antrian:sync` manual untuk melihat error |
| `POST /api/antrian/panggil` mengembalikan 401 | Token di Khanza tidak sama dengan `ANTRIAN_API_TOKEN`, atau token di `.env` kosong. Jalankan `config:cache` ulang bila `.env` baru diubah |
| Gambar radiologi tidak tampil | `RADIOLOGI_IMAGE_BASE_URL` / setting URL radiologi kosong atau tidak dapat diakses dari perangkat pasien |
| Perubahan `.env` tidak berpengaruh | Konfigurasi masih ter-cache: `php artisan config:clear && php artisan config:cache` |
| Error 500 setelah deploy | Periksa permission `storage/` dan `bootstrap/cache/`, lalu `storage/logs/laravel.log` |
| Logo/ikon yang diunggah tidak tampil | `php artisan storage:link` belum dijalankan |

---

## Lisensi

Dibangun di atas framework Laravel yang berlisensi [MIT](https://opensource.org/licenses/MIT).
