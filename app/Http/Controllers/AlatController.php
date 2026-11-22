<?php

namespace App\Http\Controllers;

use App\Models\Alat;
use Illuminate\View\View;

/**
 * Katalog alat untuk semua pengguna yang login (route alat.*).
 * Alat rusak berat tetap tampil agar mahasiswa tahu statusnya, tetapi tidak dapat dipinjam.
 */
class AlatController extends Controller
{
    public function index(): View
    {
        $alat = Alat::orderBy('nama')->paginate(10);

        return view('alat.index', ['alat' => $alat]);
    }

    public function show(Alat $alat): View
    {
        return view('alat.show', ['alat' => $alat]);
    }
}
