<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RuangRequest;
use App\Models\Ruang;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD Ruang untuk Admin Lab (route admin.ruang.*).
 */
class RuangController extends Controller
{
    public function index(): View
    {
        $ruang = Ruang::orderBy('nama')->paginate(10);

        return view('admin.ruang.index', [
            'ruang' => $ruang,
            'daftarStatus' => Ruang::DAFTAR_STATUS,
        ]);
    }

    public function create(): View
    {
        return view('admin.ruang.create', [
            'daftarStatus' => Ruang::DAFTAR_STATUS,
        ]);
    }

    public function store(RuangRequest $request): RedirectResponse
    {
        $ruang = Ruang::create($request->validated());

        return redirect()
            ->route('admin.ruang.index')
            ->with('success', "Ruang {$ruang->nama} berhasil ditambahkan.");
    }

    public function show(Ruang $ruang): View
    {
        return view('admin.ruang.show', ['ruang' => $ruang]);
    }

    public function edit(Ruang $ruang): View
    {
        return view('admin.ruang.edit', [
            'ruang' => $ruang,
            'daftarStatus' => Ruang::DAFTAR_STATUS,
        ]);
    }

    public function update(RuangRequest $request, Ruang $ruang): RedirectResponse
    {
        $ruang->update($request->validated());

        return redirect()
            ->route('admin.ruang.index')
            ->with('success', "Ruang {$ruang->nama} berhasil diperbarui.");
    }

    public function destroy(Ruang $ruang): RedirectResponse
    {
        // Aturan R3: ruang yang punya riwayat peminjaman tidak boleh dihapus; Admin menonaktifkannya.
        if ($ruang->peminjaman()->exists()) {
            return redirect()
                ->route('admin.ruang.index')
                ->with('error', "Ruang {$ruang->nama} tidak dapat dihapus karena memiliki riwayat peminjaman. Ubah statusnya menjadi Nonaktif.");
        }

        $ruang->delete();

        return redirect()
            ->route('admin.ruang.index')
            ->with('success', "Ruang {$ruang->nama} berhasil dihapus.");
    }
}
