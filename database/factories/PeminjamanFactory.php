<?php

namespace Database\Factories;

use App\Models\Peminjaman;
use App\Models\Ruang;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Peminjaman>
 */
class PeminjamanFactory extends Factory
{
    protected $model = Peminjaman::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'ruang_id' => Ruang::factory(),
            'tanggal' => now()->addDays(2)->toDateString(),
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '11:00:00',
            'keperluan' => 'Praktikum Pemrograman Web kelompok 01.',
            'status' => Peminjaman::STATUS_DIAJUKAN,
            'catatan_admin' => null,
            'diproses_oleh' => null,
            'diproses_pada' => null,
            'dipinjam_pada' => null,
            'dikembalikan_pada' => null,
        ];
    }

    public function disetujui(?User $admin = null): static
    {
        return $this->state(fn () => [
            'status' => Peminjaman::STATUS_DISETUJUI,
            'diproses_oleh' => $admin?->id ?? User::factory()->admin(),
            'diproses_pada' => now(),
        ]);
    }

    public function ditolak(?User $admin = null, string $catatan = 'Jadwal ruang tidak tersedia.'): static
    {
        return $this->state(fn () => [
            'status' => Peminjaman::STATUS_DITOLAK,
            'catatan_admin' => $catatan,
            'diproses_oleh' => $admin?->id ?? User::factory()->admin(),
            'diproses_pada' => now(),
        ]);
    }

    public function dipinjam(?User $admin = null): static
    {
        return $this->state(fn () => [
            'status' => Peminjaman::STATUS_DIPINJAM,
            'diproses_oleh' => $admin?->id ?? User::factory()->admin(),
            'diproses_pada' => now()->subHour(),
            'dipinjam_pada' => now(),
        ]);
    }

    public function dikembalikan(?User $admin = null): static
    {
        return $this->state(fn () => [
            'status' => Peminjaman::STATUS_DIKEMBALIKAN,
            'diproses_oleh' => $admin?->id ?? User::factory()->admin(),
            'diproses_pada' => now()->subHours(3),
            'dipinjam_pada' => now()->subHours(2),
            'dikembalikan_pada' => now(),
        ]);
    }

    public function dibatalkan(): static
    {
        return $this->state(fn () => [
            'status' => Peminjaman::STATUS_DIBATALKAN,
        ]);
    }
}
