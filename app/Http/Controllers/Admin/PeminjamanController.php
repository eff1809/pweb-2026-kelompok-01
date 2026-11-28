<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetujuiPeminjamanRequest;
use App\Http\Requests\TolakPeminjamanRequest;
use App\Models\Peminjaman;
use App\Services\KetersediaanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Pengelolaan persetujuan, penolakan, penyerahan, dan pengembalian peminjaman untuk Admin Lab.
 */
class PeminjamanController extends Controller
{
    public function index(Request $request): View
    {
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $status = $request->query('status');
        $statusValid = is_string($status) && array_key_exists($status, Peminjaman::DAFTAR_STATUS)
            ? $status
            : null;
        $dari = $this->tanggalValid($request->query('dari'));
        $sampai = $this->tanggalValid($request->query('sampai'));

        $peminjaman = Peminjaman::query()
            ->with(['user', 'ruang', 'alat'])
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
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.peminjaman.index', [
            'peminjaman' => $peminjaman,
            'daftarStatus' => Peminjaman::DAFTAR_STATUS,
            'filter' => [
                'q' => $q,
                'status' => $statusValid,
                'dari' => $dari,
                'sampai' => $sampai,
            ],
        ]);
    }

    public function show(Peminjaman $peminjaman): View
    {
        $peminjaman->load(['user', 'ruang', 'alat', 'pemroses']);

        return view('admin.peminjaman.show', [
            'peminjaman' => $peminjaman,
        ]);
    }

    public function setujui(
        SetujuiPeminjamanRequest $request,
        Peminjaman $peminjaman,
        KetersediaanService $ketersediaan,
    ): RedirectResponse {
        Gate::authorize('setujui', $peminjaman);

        $pesanError = DB::transaction(function () use ($request, $peminjaman, $ketersediaan): ?string {
            $terkunci = Peminjaman::query()
                ->with(['ruang', 'alat'])
                ->lockForUpdate()
                ->findOrFail($peminjaman->id);

            if (! $terkunci->isDiajukan()) {
                return 'Pengajuan ini sudah diproses sebelumnya.';
            }

            $konflik = $ketersediaan->cekUlangUntukPersetujuan($terkunci);

            if ($konflik !== null) {
                return $konflik;
            }

            $terkunci->update([
                'status' => Peminjaman::STATUS_DISETUJUI,
                'catatan_admin' => $request->validated('catatan_admin'),
                'diproses_oleh' => $request->user()->id,
                'diproses_pada' => now(),
            ]);

            return null;
        });

        if ($pesanError !== null) {
            return redirect()
                ->route('admin.peminjaman.show', $peminjaman)
                ->with('error', $pesanError);
        }

        return redirect()
            ->route('admin.peminjaman.show', $peminjaman)
            ->with('success', 'Pengajuan peminjaman berhasil disetujui.');
    }

    public function tolak(TolakPeminjamanRequest $request, Peminjaman $peminjaman): RedirectResponse
    {
        Gate::authorize('tolak', $peminjaman);

        $peminjaman->update([
            'status' => Peminjaman::STATUS_DITOLAK,
            'catatan_admin' => $request->validated('catatan_admin'),
            'diproses_oleh' => $request->user()->id,
            'diproses_pada' => now(),
        ]);

        return redirect()
            ->route('admin.peminjaman.show', $peminjaman)
            ->with('success', 'Pengajuan peminjaman telah ditolak.');
    }

    public function serahkan(Peminjaman $peminjaman): RedirectResponse
    {
        Gate::authorize('serahkan', $peminjaman);

        $peminjaman->update([
            'status' => Peminjaman::STATUS_DIPINJAM,
            'dipinjam_pada' => now(),
        ]);

        return redirect()
            ->route('admin.peminjaman.show', $peminjaman)
            ->with('success', 'Penyerahan ruang/alat berhasil dicatat (status: Dipinjam).');
    }

    public function kembalikan(Peminjaman $peminjaman): RedirectResponse
    {
        Gate::authorize('kembalikan', $peminjaman);

        $peminjaman->update([
            'status' => Peminjaman::STATUS_DIKEMBALIKAN,
            'dikembalikan_pada' => now(),
        ]);

        return redirect()
            ->route('admin.peminjaman.show', $peminjaman)
            ->with('success', 'Pengembalian ruang/alat berhasil dicatat.');
    }

    private function tanggalValid(mixed $nilai): ?string
    {
        if (! is_string($nilai) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai)) {
            return null;
        }

        return $nilai;
    }
}
