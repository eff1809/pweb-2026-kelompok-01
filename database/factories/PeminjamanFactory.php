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
    public function definition(): array
    {
        $mulai = fake()->numberBetween(8, 14);

        return [
            'user_id' => User::factory(),
            'ruang_id' => Ruang::factory(),
            'tanggal' => now()->addDays(fake()->numberBetween(1, 14))->toDateString(),
            'jam_mulai' => sprintf('%02d:00:00', $mulai),
            'jam_selesai' => sprintf('%02d:00:00', $mulai + fake()->numberBetween(1, 3)),
            'keperluan' => fake()->randomElement([
                'Praktikum konfigurasi jaringan komputer',
                'Kerja kelompok tugas besar mata kuliah',
                'Latihan persiapan ujian praktikum',
                'Pengambilan data penelitian tugas akhir',
                'Perakitan prototipe proyek mata kuliah',
                'Produksi konten dokumentasi kegiatan himpunan',
                'Gladi presentasi proyek akhir semester',
                'Asistensi tugas pemrograman',
            ]),
            'status' => Peminjaman::STATUS_DIAJUKAN,
            'catatan_admin' => null,
            'diproses_oleh' => null,
            'diproses_pada' => null,
            'dipinjam_pada' => null,
            'dikembalikan_pada' => null,
        ];
    }

    /** Admin pemroses (dibuat dengan state, tidak bergantung pada state UserFactory). */
    private function admin()
    {
        return User::factory()->state(['peran' => 'admin', 'nim' => null]);
    }

    public function disetujui(): static
    {
        return $this->state(fn () => [
            'status' => Peminjaman::STATUS_DISETUJUI,
            'diproses_oleh' => $this->admin(),
            'diproses_pada' => now(),
        ]);
    }

    public function ditolak(): static
    {
        return $this->state(fn () => [
            'status' => Peminjaman::STATUS_DITOLAK,
            'catatan_admin' => 'Jadwal ruangan sudah terpakai pada waktu tersebut.',
            'diproses_oleh' => $this->admin(),
            'diproses_pada' => now(),
        ]);
    }

    public function dipinjam(): static
    {
        return $this->state(fn () => [
            'status' => Peminjaman::STATUS_DIPINJAM,
            'diproses_oleh' => $this->admin(),
            'diproses_pada' => now()->subDay(),
            'dipinjam_pada' => now(),
        ]);
    }

    public function dikembalikan(): static
    {
        return $this->state(fn () => [
            'status' => Peminjaman::STATUS_DIKEMBALIKAN,
            'diproses_oleh' => $this->admin(),
            'diproses_pada' => now()->subDays(3),
            'dipinjam_pada' => now()->subDays(2),
            'dikembalikan_pada' => now()->subDay(),
        ]);
    }

    public function dibatalkan(): static
    {
        return $this->state(fn () => ['status' => Peminjaman::STATUS_DIBATALKAN]);
    }

    /** Peminjaman alat saja (tanpa ruang). */
    public function tanpaRuang(): static
    {
        return $this->state(fn () => ['ruang_id' => null]);
    }
}
