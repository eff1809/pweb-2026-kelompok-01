<?php

namespace App\Http\Controllers;

use App\Models\Ruang;
use Illuminate\View\View;

/**
 * Katalog ruang untuk semua pengguna yang login (route ruang.*).
 * Ruang nonaktif tidak ditampilkan di katalog.
 */
class RuangController extends Controller
{
    public function index(): View
    {
        $ruang = Ruang::where('status', '!=', Ruang::STATUS_NONAKTIF)
            ->orderBy('nama')
            ->paginate(10);

        return view('ruang.index', ['ruang' => $ruang]);
    }

    public function show(Ruang $ruang): View
    {
        abort_if($ruang->status === Ruang::STATUS_NONAKTIF, 404);

        return view('ruang.show', ['ruang' => $ruang]);
    }
}
