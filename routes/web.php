<?php

use App\Http\Controllers\ProfileController;
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
});

// Halaman khusus Admin Lab
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        // CRUD ruang dan alat (Fauzan), persetujuan peminjaman, laporan PDF
    });

// Halaman khusus Mahasiswa
Route::middleware(['auth', 'role:mahasiswa'])
    ->group(function () {
        // Pengajuan peminjaman dan riwayat
    });

require __DIR__.'/auth.php';
