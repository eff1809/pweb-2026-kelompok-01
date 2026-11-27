<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('ruang_id')->nullable()->constrained('ruang')->restrictOnDelete();
            $table->date('tanggal');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('keperluan');
            $table->enum('status', [
                'diajukan', 'disetujui', 'ditolak', 'dipinjam', 'dikembalikan', 'dibatalkan',
            ])->default('diajukan');
            $table->text('catatan_admin')->nullable();
            $table->foreignId('diproses_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('diproses_pada')->nullable();
            $table->dateTime('dipinjam_pada')->nullable();
            $table->dateTime('dikembalikan_pada')->nullable();
            $table->timestamps();

            // Mempercepat pengecekan bentrok jadwal ruang dan filter status
            $table->index(['ruang_id', 'tanggal']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman');
    }
};
