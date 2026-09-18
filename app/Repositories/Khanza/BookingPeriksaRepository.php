<?php

namespace App\Repositories\Khanza;

use App\Models\Khanza\BookingPeriksa;
use Illuminate\Support\Carbon;

/**
 * Writes to SIMRS Khanza's `booking_periksa` table — the public
 * "Daftar Pasien Baru" intake, for visitors who don't have a
 * `no_rkm_medis` yet. See the write-target rule in .ai/rules/khanza.md.
 */
class BookingPeriksaRepository
{
    public function create(
        string $nama,
        string $alamat,
        string $noTelp,
        ?string $email,
        string $kdPoli,
        Carbon $tanggal,
        ?string $tambahanPesan,
    ): BookingPeriksa {
        $tanggalBooking = Carbon::now();

        return BookingPeriksa::query()->create([
            'no_booking' => $this->nextNoBooking($tanggalBooking),
            'tanggal_booking' => $tanggalBooking->toDateString(),
            'tanggal' => $tanggal->toDateString(),
            'nama' => $nama,
            'alamat' => $alamat,
            'no_telp' => $noTelp,
            'email' => $email,
            'kd_poli' => $kdPoli,
            'tambahan_pesan' => $tambahanPesan,
            'status' => 'Belum',
        ]);
    }

    private function nextNoBooking(Carbon $tanggalBooking): string
    {
        $prefix = $tanggalBooking->format('Ymd');

        $count = BookingPeriksa::query()
            ->whereDate('tanggal_booking', $tanggalBooking)
            ->count();

        return $prefix.str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }
}
