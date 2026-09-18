---
paths:
  - 'app/Repositories/Khanza/**'
---

# Repositories Khanza

## SuratRepository queries Khanza's 13 surat tables via query builder, not Eloquent models
Khanza has no single "surat" history table — patient certificate letters (surat sakit, surat keterangan sehat, surat rawat inap, etc.) each live in their own table, all keyed by `no_rawat`, with a differently-named date column per table. SuratRepository (app/Repositories/Khanza/SuratRepository.php) unions all 13 via `DB::connection('sik')->table(...)` directly in the repository rather than creating 13 near-duplicate Eloquent models — the ReadOnlyFromKhanza trait guard is unnecessary here since the query builder only ever SELECTs. Internal admin/consent forms (surat_masuk, surat_keluar, surat_persetujuan_*, surat_pernyataan_*, surat_penolakan_*, bridging_surat_*, surat_pemesanan_*, toko_surat_pemesanan, riwayat_surat_peringatan) are intentionally excluded — those aren't letters issued to the patient. If a new patient-facing surat table is added in Khanza, add it to the `LETTER_TYPES` const with its date column and label.
