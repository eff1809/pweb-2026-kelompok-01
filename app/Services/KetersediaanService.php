<?php

namespace App\Services;

use App\Models\Alat;
use App\Models\Peminjaman;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Layanan pengecekan bentrok jadwal ruang dan ketersediaan stok alat.
 * Dipakai bersama oleh Form Request pengajuan/ubah dan aksi persetujuan Admin (aturan P7).
 */
class KetersediaanService
{
    public function normalisasiJam(string $jam): string
    {
        return Carbon::parse($jam)->format('H:i:s');
    }

    /**
     * Mengecek apakah ruang bentrok dengan peminjaman berstatus disetujui/dipinjam pada rentang yang beririsan.
     */
    public function ruangBentrok(
        int $ruangId,
        string $tanggal,
        string $jamMulai,
        string $jamSelesai,
        ?int $kecualiId = null,
        bool $lock = false,
    ): bool {
        $mulai = $this->normalisasiJam($jamMulai);
        $selesai = $this->normalisasiJam($jamSelesai);

        $query = Peminjaman::query()
            ->where('ruang_id', $ruangId)
            ->whereDate('tanggal', $tanggal)
            ->whereIn('status', Peminjaman::STATUS_AKTIF)
            ->where('jam_mulai', '<', $selesai)
            ->where('jam_selesai', '>', $mulai)
            ->when($kecualiId !== null, fn ($q) => $q->where('id', '!=', $kecualiId));

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }

    /**
     * Menghitung sisa stok alat yang tersedia pada tanggal dan rentang jam tertentu.
     */
    public function stokTersedia(
        Alat $alat,
        string $tanggal,
        string $jamMulai,
        string $jamSelesai,
        ?int $kecualiId = null,
        bool $lock = false,
    ): int {
        $mulai = $this->normalisasiJam($jamMulai);
        $selesai = $this->normalisasiJam($jamSelesai);

        $query = DB::table('peminjaman_alat')
            ->join('peminjaman', 'peminjaman.id', '=', 'peminjaman_alat.peminjaman_id')
            ->where('peminjaman_alat.alat_id', $alat->id)
            ->whereDate('peminjaman.tanggal', $tanggal)
            ->whereIn('peminjaman.status', Peminjaman::STATUS_AKTIF)
            ->where('peminjaman.jam_mulai', '<', $selesai)
            ->where('peminjaman.jam_selesai', '>', $mulai)
            ->when($kecualiId !== null, fn ($q) => $q->where('peminjaman.id', '!=', $kecualiId));

        if ($lock) {
            $query->lockForUpdate();
        }

        $terpakai = (int) $query->sum('peminjaman_alat.jumlah');

        return max(0, $alat->jumlah_total - $terpakai);
    }

    /**
     * Memeriksa ulang ketersediaan ruang dan seluruh alat untuk sebuah peminjaman (dipakai saat Admin menyetujui).
     * Mengembalikan null jika seluruh item tersedia, atau pesan kesalahan jika bentrok/tidak tersedia.
     */
    public function cekUlangUntukPersetujuan(Peminjaman $peminjaman): ?string
    {
        $tanggal = $peminjaman->tanggal->toDateString();

        if ($peminjaman->ruang_id !== null) {
            $ruang = $peminjaman->ruang;

            if ($ruang === null || ! $ruang->isTersedia()) {
                return 'Tidak dapat disetujui: ruang sedang tidak tersedia untuk dipinjam.';
            }

            if ($this->ruangBentrok(
                $peminjaman->ruang_id,
                $tanggal,
                $peminjaman->jam_mulai,
                $peminjaman->jam_selesai,
                $peminjaman->id,
                lock: true,
            )) {
                return 'Tidak dapat disetujui: ruang/alat sudah terpakai oleh peminjaman lain.';
            }
        }

        foreach ($peminjaman->alat as $alat) {
            if (! $alat->bisaDipinjam()) {
                return "Tidak dapat disetujui: {$alat->nama} sedang rusak berat dan tidak dapat dipinjam.";
            }

            $tersedia = $this->stokTersedia(
                $alat,
                $tanggal,
                $peminjaman->jam_mulai,
                $peminjaman->jam_selesai,
                $peminjaman->id,
                lock: true,
            );

            if ((int) $alat->pivot->jumlah > $tersedia) {
                return 'Tidak dapat disetujui: ruang/alat sudah terpakai oleh peminjaman lain.';
            }
        }

        return null;
    }
}
