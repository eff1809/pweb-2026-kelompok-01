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
        // TODO (setelah tabel peminjaman ada): jumlah_total tidak boleh di bawah unit yang sedang dipinjam (aturan A5).
        $alat->update($request->validated());

        return redirect()
            ->route('admin.alat.index')
            ->with('success', "Alat {$alat->nama} ({$alat->kode}) berhasil diperbarui.");
    }

    public function destroy(Alat $alat): RedirectResponse
    {
        // TODO (setelah tabel peminjaman ada): tolak penghapusan jika alat punya riwayat peminjaman (aturan A4).
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
