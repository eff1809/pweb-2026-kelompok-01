<?php

namespace Database\Factories;

use App\Models\Ruang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ruang>
 */
class RuangFactory extends Factory
{
    protected $model = Ruang::class;

    public function definition(): array
    {
        $jenis = fake()->randomElement([
            'Laboratorium Jaringan Komputer',
            'Laboratorium Rekayasa Perangkat Lunak',
            'Laboratorium Kecerdasan Buatan',
            'Laboratorium Sistem Tertanam',
            'Laboratorium Multimedia',
            'Ruang Seminar',
        ]);

        return [
            'nama' => $jenis.' '.fake()->unique()->numberBetween(1, 99),
            'lokasi' => 'Gedung Teknik Informatika, Lantai '.fake()->numberBetween(1, 4),
            'kapasitas' => fake()->randomElement([20, 30, 40, 50]),
            'deskripsi' => fake()->optional()->sentence(12),
            'status' => Ruang::STATUS_TERSEDIA,
        ];
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
