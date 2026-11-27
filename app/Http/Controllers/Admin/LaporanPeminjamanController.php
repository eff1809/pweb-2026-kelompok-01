<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Ekspor laporan rekap peminjaman ruang dan alat laboratorium ke format PDF (khusus Admin Lab).
 */
class LaporanPeminjamanController extends Controller
{
    public function pdf(Request $request): Response
    {
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $status = $request->query('status');
        $statusValid = is_string($status) && array_key_exists($status, Peminjaman::DAFTAR_STATUS)
            ? $status
            : null;
        $dari = $this->tanggalValid($request->query('dari'));
        $sampai = $this->tanggalValid($request->query('sampai'));

        $peminjaman = Peminjaman::query()
            ->with(['user', 'ruang', 'alat', 'pemroses'])
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($sub) use ($q): void {
                    $sub->where('keperluan', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($userQuery) use ($q): void {
                            $userQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('nim', 'like', "%{$q}%");
                        });
                });
            })
            ->when($statusValid !== null, fn ($query) => $query->where('status', $statusValid))
            ->when($dari !== null, fn ($query) => $query->whereDate('tanggal', '>=', $dari))
            ->when($sampai !== null, fn ($query) => $query->whereDate('tanggal', '<=', $sampai))
            ->orderByDesc('tanggal')
            ->orderByDesc('jam_mulai')
            ->get();

        $ringkasan = [
            'total' => $peminjaman->count(),
            'per_status' => collect(Peminjaman::DAFTAR_STATUS)->mapWithKeys(
                fn (string $label, string $kode) => [
                    $kode => [
                        'label' => $label,
                        'jumlah' => $peminjaman->where('status', $kode)->count(),
                    ],
                ],
            )->all(),
        ];

        $pdf = Pdf::loadView('admin.laporan.peminjaman-pdf', [
            'peminjaman' => $peminjaman,
            'ringkasan' => $ringkasan,
            'daftarStatus' => Peminjaman::DAFTAR_STATUS,
            'filter' => [
                'q' => $q,
                'status' => $statusValid,
                'dari' => $dari,
                'sampai' => $sampai,
            ],
            'dicetakOleh' => $request->user(),
            'dicetakPada' => now(),
        ])->setPaper('a4', 'landscape');

        $namaFile = 'rekap-peminjaman-lab-'.now()->format('Ymd-His').'.pdf';

        if ($request->boolean('preview')) {
            return $pdf->stream($namaFile);
        }

        return $pdf->download($namaFile);
    }

    private function tanggalValid(mixed $nilai): ?string
    {
        if (! is_string($nilai) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return null;
        }

        return $nilai;
    }
}
