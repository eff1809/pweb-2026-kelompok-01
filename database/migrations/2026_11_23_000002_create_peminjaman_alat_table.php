<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peminjaman_alat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peminjaman_id')->constrained('peminjaman')->cascadeOnDelete();
            $table->foreignId('alat_id')->constrained('alat')->restrictOnDelete();
            $table->unsignedInteger('jumlah')->default(1);

            // Satu alat hanya muncul sekali dalam satu peminjaman
            $table->unique(['peminjaman_id', 'alat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peminjaman_alat');
    }
};
