<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\MembacaFilter;
use App\Models\Ruang;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Katalog ruang untuk semua pengguna yang login (route ruang.*).
 * Ruang nonaktif tidak ditampilkan di katalog.
 */
class RuangController extends Controller
{
    use MembacaFilter;

    /** Status yang boleh dipakai sebagai filter katalog (nonaktif tidak pernah tampil). */
    private const STATUS_KATALOG = [Ruang::STATUS_TERSEDIA, Ruang::STATUS_PERBAIKAN];

    public function index(Request $request): View
    {
        $filter = [
            'q' => $this->teksFilter($request, 'q'),
            'status' => $this->pilihanFilter($request, 'status', self::STATUS_KATALOG),
        ];

        $ruang = Ruang::query()
            ->where('status', '!=', Ruang::STATUS_NONAKTIF)
            ->cari($filter['q'])
            ->when($filter['status'], fn ($query, $status) => $query->where('status', $status))
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString();

        return view('ruang.index', [
            'ruang' => $ruang,
            'filter' => $filter,
            'daftarStatus' => array_intersect_key(Ruang::DAFTAR_STATUS, array_flip(self::STATUS_KATALOG)),
        ]);
    }

    public function show(Ruang $ruang): View
    {
        abort_if($ruang->status === Ruang::STATUS_NONAKTIF, 404);

        return view('ruang.show', ['ruang' => $ruang]);
    }
}
