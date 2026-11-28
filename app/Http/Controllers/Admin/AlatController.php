<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\KelolaFoto;
use App\Http\Controllers\Concerns\MembacaFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\AlatRequest;
use App\Models\Alat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRUD Alat untuk Admin Lab (route admin.alat.*).
 */
class AlatController extends Controller
{
    use KelolaFoto, MembacaFilter;

    public function index(Request $request): View
    {
        $daftarKategori = Alat::daftarKategori();

        $filter = [
            'q' => $this->teksFilter($request, 'q'),
            'kategori' => $this->pilihanFilter($request, 'kategori', $daftarKategori),
            'kondisi' => $this->pilihanFilter($request, 'kondisi', array_keys(Alat::DAFTAR_KONDISI)),
        ];

        $alat = Alat::query()
            ->cari($filter['q'])
            ->when($filter['kategori'], fn ($query, $kategori) => $query->where('kategori', $kategori))
            ->when($filter['kondisi'], fn ($query, $kondisi) => $query->where('kondisi', $kondisi))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('admin.alat.index', [
            'alat' => $alat,
            'filter' => $filter,
            'daftarKategori' => $daftarKategori,
            'daftarKondisi' => Alat::DAFTAR_KONDISI,
        ]);
    }

    public function create(): View
    {
        return view('admin.alat.create', [
            'daftarKondisi' => Alat::DAFTAR_KONDISI,
            'daftarKategori' => Alat::daftarKategori(),
        ]);
    }

    public function store(AlatRequest $request): RedirectResponse
    {
        $alat = Alat::create($this->dataDenganFoto($request, 'alat'));

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
            'daftarKategori' => Alat::daftarKategori(),
        ]);
    }

    public function update(AlatRequest $request, Alat $alat): RedirectResponse
    {
        // Aturan A5 (jumlah_total >= unit yang sedang dipinjam) divalidasi di AlatRequest.
        $alat->update($this->dataDenganFoto($request, 'alat'));

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
}
