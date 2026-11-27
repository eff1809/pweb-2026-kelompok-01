<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Http\Requests\PeminjamanRequest;
use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\Ruang;
use App\Services\KetersediaanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Pengajuan, ubah, pembatalan, dan riwayat peminjaman untuk Mahasiswa.
 */
class PeminjamanController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $statusValid = is_string($status) && array_key_exists($status, Peminjaman::DAFTAR_STATUS)
            ? $status
            : null;

        $peminjaman = Peminjaman::query()
            ->with(['ruang', 'alat'])
            ->where('user_id', $request->user()->id)
            ->when($statusValid !== null, fn ($q) => $q->where('status', $statusValid))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('peminjaman.index', [
            'peminjaman' => $peminjaman,
            'daftarStatus' => Peminjaman::DAFTAR_STATUS,
            'filter' => [
                'status' => $statusValid,
            ],
        ]);
    }

    public function create(): View
    {
        return view('peminjaman.create', [
            'ruang' => Ruang::where('status', Ruang::STATUS_TERSEDIA)->orderBy('nama')->get(),
            'alat' => Alat::where('kondisi', '!=', Alat::KONDISI_RUSAK_BERAT)->orderBy('nama')->get(),
        ]);
    }

    public function store(PeminjamanRequest $request, KetersediaanService $ketersediaan): RedirectResponse
    {
        $data = $request->validated();

        $peminjaman = DB::transaction(function () use ($request, $data, $ketersediaan): Peminjaman {
            $peminjaman = Peminjaman::create([
                'user_id' => $request->user()->id,
                'ruang_id' => $data['ruang_id'] ?? null,
                'tanggal' => $data['tanggal'],
                'jam_mulai' => $ketersediaan->normalisasiJam($data['jam_mulai']),
                'jam_selesai' => $ketersediaan->normalisasiJam($data['jam_selesai']),
                'keperluan' => $data['keperluan'],
                'status' => Peminjaman::STATUS_DIAJUKAN,
            ]);

            $peminjaman->alat()->sync($this->formatPivotAlat($data['alat'] ?? []));

            return $peminjaman;
        });

        return redirect()
            ->route('peminjaman.show', $peminjaman)
            ->with('success', 'Pengajuan peminjaman berhasil dikirim dan menunggu persetujuan admin.');
    }

    public function show(Peminjaman $peminjaman): View
    {
        Gate::authorize('view', $peminjaman);

        $peminjaman->load(['user', 'ruang', 'alat', 'pemroses']);

        return view('peminjaman.show', [
            'peminjaman' => $peminjaman,
        ]);
    }

    public function edit(Peminjaman $peminjaman): View
    {
        Gate::authorize('update', $peminjaman);

        $peminjaman->load(['ruang', 'alat']);

        return view('peminjaman.edit', [
            'peminjaman' => $peminjaman,
            'ruang' => Ruang::where('status', Ruang::STATUS_TERSEDIA)->orderBy('nama')->get(),
            'alat' => Alat::where('kondisi', '!=', Alat::KONDISI_RUSAK_BERAT)->orderBy('nama')->get(),
        ]);
    }

    public function update(
        PeminjamanRequest $request,
        Peminjaman $peminjaman,
        KetersediaanService $ketersediaan,
    ): RedirectResponse {
        Gate::authorize('update', $peminjaman);

        $data = $request->validated();

        DB::transaction(function () use ($peminjaman, $data, $ketersediaan): void {
            $peminjaman->update([
                'ruang_id' => $data['ruang_id'] ?? null,
                'tanggal' => $data['tanggal'],
                'jam_mulai' => $ketersediaan->normalisasiJam($data['jam_mulai']),
                'jam_selesai' => $ketersediaan->normalisasiJam($data['jam_selesai']),
                'keperluan' => $data['keperluan'],
            ]);

            $peminjaman->alat()->sync($this->formatPivotAlat($data['alat'] ?? []));
        });

        return redirect()
            ->route('peminjaman.show', $peminjaman)
            ->with('success', 'Pengajuan peminjaman berhasil diperbarui.');
    }

    public function batal(Peminjaman $peminjaman): RedirectResponse
    {
        Gate::authorize('batal', $peminjaman);

        $peminjaman->update([
            'status' => Peminjaman::STATUS_DIBATALKAN,
        ]);

        return redirect()
            ->route('peminjaman.show', $peminjaman)
            ->with('success', 'Pengajuan peminjaman berhasil dibatalkan.');
    }

    /**
     * @param  array<int, array{alat_id: int|string, jumlah: int|string}>  $daftarAlat
     * @return array<int, array{jumlah: int}>
     */
    private function formatPivotAlat(array $daftarAlat): array
    {
        $pivot = [];

        foreach ($daftarAlat as $item) {
            $pivot[(int) $item['alat_id']] = [
                'jumlah' => (int) $item['jumlah'],
            ];
        }

        return $pivot;
    }
}
