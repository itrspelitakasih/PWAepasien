---
paths:
  - 'app/Models/Khanza/**,app/Repositories/Khanza/**'
  - 'app/Models/Khanza/BookingRegistrasi.php,app/Repositories/Khanza/BookingRegistrasiRepository.php'
---

# Khanza

## SIMRS Khanza connection is named `sik`, not `khanza`, and is read-only
The Khanza hospital DB connection in config/database.php is named `sik` (schema `sik2023_server`), not `khanza` — use `protected $connection = 'sik';` on new Khanza models.

Every Khanza model must use `App\Models\Khanza\Concerns\ReadOnlyFromKhanza` (see app/Models/Khanza/Pasien.php), which throws on save/delete. This app must never write to Khanza; wrap all reads in an `app/Repositories/Khanza/*Repository` class (see PasienRepository) rather than querying Khanza models directly from controllers/services.

Many Khanza tables use non-autoincrement, non-integer primary keys (e.g. `pasien.no_rkm_medis` is `varchar(15)`) — set `$primaryKey`, `$keyType = 'string'`, and `$incrementing = false` accordingly, and check `SHOW KEYS FROM <table>` before assuming a table's PK shape.

## booking_registrasi is the one sanctioned Khanza write target
Unlike every other table under app/Models/Khanza/** (read-only, see khanza.md), `sik.booking_registrasi` is Khanza's own external-booking intake table and this app is allowed to INSERT into it for online patient registration (pendaftaran online) — confirmed explicitly with the project owner, who pointed out this table exists for exactly this purpose.

BookingRegistrasi does NOT use the ReadOnlyFromKhanza trait — that's intentional, not an oversight. Never add it there, and never extend this write exception to any other Khanza table without the same explicit confirmation.

Composite PK is (no_rkm_medis, tanggal_periksa) — a patient can only have one booking per date; inserting a duplicate throws a PK-collision DB exception, which the repository/controller must turn into a validation error. Fields on create: no_reg = zero-padded count of existing bookings for that tanggal_periksa+kd_poli, limit_reg = 0, status = 'Belum' (loket processes it into a real reg_periksa via the actual Khanza desktop app — this project never converts a booking into a registration itself), waktu_kunjungan = tanggal_periksa + that day's jadwal.jam_mulai, kd_pj = patient's most recent reg_periksa.kd_pj (fallback to a penjab picker only if none exists).
