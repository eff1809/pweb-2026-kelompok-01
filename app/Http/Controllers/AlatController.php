<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MembacaFilter;
use App\Models\Alat;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Katalog alat untuk semua pengguna yang login (route alat.*).
 * Alat rusak berat tetap tampil agar mahasiswa tahu statusnya, tetapi tidak dapat dipinjam.
 */
class AlatController extends Controller
{
    use MembacaFilter;

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

        return view('alat.index', [
            'alat' => $alat,
            'filter' => $filter,
            'daftarKategori' => $daftarKategori,
            'daftarKondisi' => Alat::DAFTAR_KONDISI,
        ]);
    }

    public function show(Alat $alat): View
    {
        return view('alat.show', ['alat' => $alat]);
    }
}
