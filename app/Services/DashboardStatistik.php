<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Query agregat untuk dashboard Admin (dipakai grafik Chart.js).
 */
class DashboardStatistik
{
    /** Urutan alur status, dipakai supaya grafik status selalu berurutan dan lengkap. */
    public const URUTAN_STATUS = [
        'diajukan', 'disetujui', 'dipinjam', 'dikembalikan', 'ditolak', 'dibatalkan',
    ];

    /**
     * Status yang TIDAK dihitung sebagai "peminjaman" pada grafik per bulan
     * dan alat terpopuler (pengajuan yang gagal tidak pernah benar-benar terjadi).
     */
    public const STATUS_TIDAK_DIHITUNG = ['ditolak', 'dibatalkan'];

    /** Semua data dashboard sekaligus (untuk satu panggilan di controller). */
    public function semua(int $jumlahBulan = 6, int $jumlahAlat = 5): array
    {
        return [
            'perBulan' => $this->peminjamanPerBulan($jumlahBulan),
            'alatTerpopuler' => $this->alatPalingSeringDipinjam($jumlahAlat),
            'perStatus' => $this->peminjamanPerStatus(),
        ];
    }

    /**
     * Jumlah peminjaman per bulan untuk N bulan terakhir (termasuk bulan ini).
     * Bulan tanpa data tetap muncul dengan nilai 0 agar sumbu grafik tidak bolong.
     *
     * @return array{labels: string[], data: int[]}
     */
    public function peminjamanPerBulan(int $jumlahBulan = 6): array
    {
        $awal = Carbon::today()->startOfMonth()->subMonths($jumlahBulan - 1);
        $akhir = Carbon::today()->endOfMonth();

        $ekspresiBulan = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', tanggal)"
            : "DATE_FORMAT(tanggal, '%Y-%m')";

        $hitung = DB::table('peminjaman')
            ->selectRaw("$ekspresiBulan as bulan, COUNT(*) as total")
            ->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->whereNotIn('status', self::STATUS_TIDAK_DIHITUNG)
            ->groupBy('bulan')
            ->pluck('total', 'bulan');

        $labels = [];
        $data = [];
        for ($i = 0; $i < $jumlahBulan; $i++) {
            $bulan = $awal->copy()->addMonthsNoOverflow($i);
            $labels[] = $bulan->locale('id')->translatedFormat('M Y');
            $data[] = (int) ($hitung[$bulan->format('Y-m')] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Alat yang paling sering dipinjam, diurutkan dari total unit terbanyak.
     *
     * @return array{labels: string[], data: int[], detail: array<int, array{kode: string, nama: string, total_unit: int, kali_dipinjam: int}>}
     */
    public function alatPalingSeringDipinjam(int $limit = 5): array
    {
        $baris = DB::table('peminjaman_alat as pa')
            ->join('peminjaman as p', 'p.id', '=', 'pa.peminjaman_id')
            ->join('alat as a', 'a.id', '=', 'pa.alat_id')
            ->whereNotIn('p.status', self::STATUS_TIDAK_DIHITUNG)
            ->groupBy('a.id', 'a.kode', 'a.nama')
            ->selectRaw('a.kode, a.nama, SUM(pa.jumlah) as total_unit, COUNT(DISTINCT p.id) as kali_dipinjam')
            ->orderByDesc('total_unit')
            ->orderByDesc('kali_dipinjam')
            ->orderBy('a.nama')
            ->limit($limit)
            ->get();

        $detail = $baris->map(fn ($r) => [
            'kode' => $r->kode,
            'nama' => $r->nama,
            'total_unit' => (int) $r->total_unit,
            'kali_dipinjam' => (int) $r->kali_dipinjam,
        ])->all();

        return [
            'labels' => array_column($detail, 'nama'),
            'data' => array_column($detail, 'total_unit'),
            'detail' => $detail,
        ];
    }

    /**
     * Jumlah peminjaman per status. Semua status selalu ada (0 jika kosong),
     * dengan urutan sesuai alur peminjaman.
     *
     * @return array{labels: string[], data: int[]}
     */
    public function peminjamanPerStatus(): array
    {
        $hitung = DB::table('peminjaman')
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'labels' => self::URUTAN_STATUS,
            'data' => array_map(fn ($s) => (int) ($hitung[$s] ?? 0), self::URUTAN_STATUS),
        ];
    }
}
