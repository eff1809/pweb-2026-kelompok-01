<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AlatController;
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
});

// Halaman khusus Admin Lab
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // CRUD ruang dan alat
        Route::resource('ruang', Admin\RuangController::class);
        Route::resource('alat', Admin\AlatController::class);

        // Persetujuan peminjaman, laporan PDF
    });

// Halaman khusus Mahasiswa
Route::middleware(['auth', 'role:mahasiswa'])
    ->group(function () {
        // Pengajuan peminjaman dan riwayat
    });

require __DIR__.'/auth.php';
