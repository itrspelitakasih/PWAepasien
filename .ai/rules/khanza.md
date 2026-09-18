---
paths:
  - 'app/Models/Khanza/**,app/Repositories/Khanza/**'
  - 'app/Models/Khanza/BookingRegistrasi.php,app/Repositories/Khanza/BookingRegistrasiRepository.php'
  - 'app/Models/Khanza/BookingPeriksa.php,app/Repositories/Khanza/BookingPeriksaRepository.php'
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

## booking_periksa is a second sanctioned Khanza write target, for people with no `no_rkm_medis` yet
`sik.booking_periksa` is Khanza's own public "Daftar Pasien Baru" intake table — for visitors who aren't a registered patient at all, unlike `booking_registrasi` which requires an existing `no_rkm_medis`. This app's patient-portal welcome page (`patient.register` / `PatientRegistrationRequestController`) is allowed to INSERT into it — confirmed explicitly with the project owner the same way `booking_registrasi` was.

BookingPeriksa does NOT use the ReadOnlyFromKhanza trait, same reasoning as BookingRegistrasi. Never extend this write exception to any other Khanza table without the same explicit confirmation.

Confirmed columns (from `rptBookingPeriksa.jrxml` in the Khanza report set — this dev environment's `sik` DB doesn't have the table at all, so its exact column types/nullability/`status` enum values are NOT independently verified against a live schema): `no_booking` (PK), `tanggal_booking`, `tanggal` (intended visit date), `nama`, `alamat`, `no_telp`, `email`, `kd_poli`, `tambahan_pesan`, `status`. No `no_rkm_medis`, `kd_dokter`, `nik`, `tgl_lahir`, or `jk` column is confirmed to exist, so the register form only collects fields with a confirmed column. BookingPeriksaRepository::create() sets `no_booking` to `tanggal_booking` formatted `Ymd` plus a zero-padded 4-digit count of that day's bookings, and `status` to `'Belum'` (mirroring `booking_registrasi`'s pending convention — not independently confirmed either). Verify both the schema and the status values against the real production `sik` database before relying on this in production.

## antripoli is fine to read for "which poli codes exist", not for per-ticket state
`sik.antripoli` (columns: `kd_dokter`, `kd_poli`, `status`, `no_rawat` — confirmed via `SHOW COLUMNS` against the dev `sik` DB) has no primary key and no ordering/date column, which is why `QueueTicket` (this app's own queue state) is deliberately seeded from `reg_periksa` instead of read from here. `AntripoliRepository::activeKdPoli()` is a narrow, sanctioned exception: it only does `SELECT DISTINCT kd_poli`, at the project owner's request, to drive the "Pilih Poli" picker in `patient.pendaftaran` (self-registration for existing patients) with poli that currently have a live queue, instead of `poliklinik`'s own `status` flag. Don't extend `Antripoli` reads beyond that distinct-code lookup (e.g. don't try to derive queue position or per-ticket status from it) without re-confirming the table's real semantics against production.
