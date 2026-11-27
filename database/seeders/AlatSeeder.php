<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlatSeeder extends Seeder
{
    public function run(): void
    {
        // [kategori, kode, nama, jumlah_total (unit layak pinjam), kondisi]
        $alat = [
            ['Mikrokontroler & IoT', 'MCU-001', 'Arduino Uno R3', 15, 'baik'],
            ['Mikrokontroler & IoT', 'MCU-002', 'ESP32 DevKit V1', 12, 'baik'],
            ['Mikrokontroler & IoT', 'MCU-003', 'Raspberry Pi 4 Model B', 6, 'baik'],
            ['Mikrokontroler & IoT', 'MCU-004', 'Sensor Ultrasonik HC-SR04', 20, 'baik'],
            ['Mikrokontroler & IoT', 'MCU-005', 'Breadboard dan Kabel Jumper (set)', 20, 'baik'],

            ['Jaringan Komputer', 'NET-001', 'Router MikroTik RB951', 5, 'baik'],
            ['Jaringan Komputer', 'NET-002', 'Switch Cisco 24 Port', 3, 'baik'],
            ['Jaringan Komputer', 'NET-003', 'Crimping Tool dan Kabel UTP (set)', 8, 'rusak_ringan'],

            ['Instrumen Ukur', 'INS-001', 'Multimeter Digital', 10, 'baik'],
            ['Instrumen Ukur', 'INS-002', 'Osiloskop Digital', 2, 'baik'],
            ['Instrumen Ukur', 'INS-003', 'Power Supply DC Variabel', 4, 'baik'],
            ['Instrumen Ukur', 'INS-004', 'Solder Station', 6, 'baik'],

            ['Perangkat Presentasi', 'PRS-001', 'Proyektor LCD', 3, 'baik'],
            ['Perangkat Presentasi', 'PRS-002', 'Layar Proyektor Portabel', 2, 'baik'],

            ['Komputer & Aksesori', 'KOM-001', 'Laptop Praktikum', 10, 'baik'],
            // Rusak berat: tidak ada unit yang layak pinjam (jumlah_total = 0)
            ['Komputer & Aksesori', 'KOM-002', 'Webcam Eksternal', 0, 'rusak_berat'],
        ];

        $rows = [];
        foreach ($alat as [$kategori, $kode, $nama, $jumlah, $kondisi]) {
            $rows[] = [
                'nama' => $nama,
                'kode' => $kode,
                'kategori' => $kategori,
                'jumlah_total' => $jumlah,
                'kondisi' => $kondisi,
                'foto' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('alat')->insert($rows);
    }
}
