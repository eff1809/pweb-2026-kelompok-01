<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AlatController;
use App\Http\Controllers\Mahasiswa;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RuangController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Katalog ruang dan alat (Admin dan Mahasiswa)
    Route::get('/ruang', [RuangController::class, 'index'])->name('ruang.index');
    Route::get('/ruang/{ruang}', [RuangController::class, 'show'])->name('ruang.show');
    Route::get('/alat', [AlatController::class, 'index'])->name('alat.index');
    Route::get('/alat/{alat}', [AlatController::class, 'show'])->name('alat.show');

    // Detail peminjaman (Pemilik atau Admin via PeminjamanPolicy)
    Route::get('/peminjaman/{peminjaman}', [Mahasiswa\PeminjamanController::class, 'show'])
        ->whereNumber('peminjaman')
        ->name('peminjaman.show');
});

// Halaman khusus Admin Lab
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // CRUD ruang dan alat
        Route::resource('ruang', Admin\RuangController::class);
        Route::resource('alat', Admin\AlatController::class);

        // Persetujuan, penyerahan, dan pengembalian peminjaman
        Route::get('/peminjaman', [Admin\PeminjamanController::class, 'index'])->name('peminjaman.index');
        Route::get('/peminjaman/{peminjaman}', [Admin\PeminjamanController::class, 'show'])->name('peminjaman.show');
        Route::patch('/peminjaman/{peminjaman}/setujui', [Admin\PeminjamanController::class, 'setujui'])->name('peminjaman.setujui');
        Route::patch('/peminjaman/{peminjaman}/tolak', [Admin\PeminjamanController::class, 'tolak'])->name('peminjaman.tolak');
        Route::patch('/peminjaman/{peminjaman}/serahkan', [Admin\PeminjamanController::class, 'serahkan'])->name('peminjaman.serahkan');
        Route::patch('/peminjaman/{peminjaman}/kembalikan', [Admin\PeminjamanController::class, 'kembalikan'])->name('peminjaman.kembalikan');

        // Laporan PDF
    });

// Halaman khusus Mahasiswa
Route::middleware(['auth', 'role:mahasiswa'])
    ->group(function () {
        // Pengajuan peminjaman dan riwayat
        Route::get('/peminjaman', [Mahasiswa\PeminjamanController::class, 'index'])->name('peminjaman.index');
        Route::get('/peminjaman/create', [Mahasiswa\PeminjamanController::class, 'create'])->name('peminjaman.create');
        Route::post('/peminjaman', [Mahasiswa\PeminjamanController::class, 'store'])->name('peminjaman.store');
        Route::get('/peminjaman/{peminjaman}/edit', [Mahasiswa\PeminjamanController::class, 'edit'])->name('peminjaman.edit');
        Route::put('/peminjaman/{peminjaman}', [Mahasiswa\PeminjamanController::class, 'update'])->name('peminjaman.update');
        Route::patch('/peminjaman/{peminjaman}/batal', [Mahasiswa\PeminjamanController::class, 'batal'])->name('peminjaman.batal');
    });

require __DIR__.'/auth.php';
