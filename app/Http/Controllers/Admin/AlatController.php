<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AlatRequest;
use App\Models\Alat;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD Alat untuk Admin Lab (route admin.alat.*).
 */
class AlatController extends Controller
{
    public function index(): View
    {
        $alat = Alat::orderBy('nama')->paginate(10);

        return view('admin.alat.index', [
            'alat' => $alat,
            'daftarKondisi' => Alat::DAFTAR_KONDISI,
        ]);
    }

    public function create(): View
    {
        return view('admin.alat.create', [
            'daftarKondisi' => Alat::DAFTAR_KONDISI,
            'daftarKategori' => $this->daftarKategori(),
        ]);
    }

    public function store(AlatRequest $request): RedirectResponse
    {
        $alat = Alat::create($request->validated());

        return redirect()
            ->route('admin.alat.index')
            ->with('success', "Alat {$alat->nama} ({$alat->kode}) berhasil ditambahkan.");
    }

    public function show(Alat $alat): View
    {
        return view('admin.alat.show', ['alat' => $alat]);
    }

    public function edit(Alat $alat): View
    {
        return view('admin.alat.edit', [
            'alat' => $alat,
            'daftarKondisi' => Alat::DAFTAR_KONDISI,
            'daftarKategori' => $this->daftarKategori(),
        ]);
    }

    public function update(AlatRequest $request, Alat $alat): RedirectResponse
    {
        // Aturan A5 (jumlah_total >= unit yang sedang dipinjam) divalidasi di AlatRequest.
        $alat->update($request->validated());

        return redirect()
            ->route('admin.alat.index')
            ->with('success', "Alat {$alat->nama} ({$alat->kode}) berhasil diperbarui.");
    }

    public function destroy(Alat $alat): RedirectResponse
    {
        // Aturan A4: alat yang punya riwayat peminjaman tidak boleh dihapus.
        if ($alat->peminjaman()->exists()) {
            return redirect()
                ->route('admin.alat.index')
                ->with('error', "Alat {$alat->nama} ({$alat->kode}) tidak dapat dihapus karena memiliki riwayat peminjaman. Ubah kondisi atau jumlahnya bila alat tidak lagi dipakai.");
        }

        $alat->delete();

        return redirect()
            ->route('admin.alat.index')
            ->with('success', "Alat {$alat->nama} ({$alat->kode}) berhasil dihapus.");
    }

    /** Kategori yang sudah dipakai, untuk saran isian (datalist) di form. */
    private function daftarKategori(): array
    {
        return Alat::query()->distinct()->orderBy('kategori')->pluck('kategori')->all();
    }
}
