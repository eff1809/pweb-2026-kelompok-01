<?php

namespace Database\Factories;

use App\Models\Alat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Alat>
 */
class AlatFactory extends Factory
{
    protected $model = Alat::class;

    public function definition(): array
    {
        [$nama, $prefix, $kategori] = fake()->randomElement([
            ['Proyektor Epson EB-X51', 'PRJ', 'Presentasi'],
            ['Router Cisco 1941', 'RTR', 'Jaringan'],
            ['Switch TP-Link 24 Port', 'SWT', 'Jaringan'],
            ['Arduino Uno R3', 'ARD', 'Mikrokontroler'],
            ['Raspberry Pi 4 Model B', 'RPI', 'Mikrokontroler'],
            ['Multimeter Digital Sanwa', 'MTR', 'Alat Ukur'],
            ['Kamera Canon EOS M50', 'KAM', 'Multimedia'],
        ]);

        return [
            'nama' => $nama,
            'kode' => $prefix.'-'.fake()->unique()->numerify('###'),
            'kategori' => $kategori,
            'jumlah_total' => fake()->numberBetween(2, 15),
            'kondisi' => Alat::KONDISI_BAIK,
        ];
    }

    public function kondisi(string $kondisi): static
    {
        return $this->state(fn () => ['kondisi' => $kondisi]);
    }
}
