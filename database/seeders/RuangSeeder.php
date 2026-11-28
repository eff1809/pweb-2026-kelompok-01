<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RuangSeeder extends Seeder
{
    public function run(): void
    {
        $lokasi = 'Gedung Teknik Informatika, Lt. ';

        // [nama, lantai, kapasitas, deskripsi, status]
        $ruang = [
            ['Lab Pemrograman Dasar', 1, 40,
                'Ruang praktikum dengan 40 komputer, proyektor, dan pendingin ruangan.', 'tersedia'],
            ['Lab Jaringan Komputer', 2, 30,
                'Dilengkapi rak jaringan, router, switch, dan meja kerja untuk praktikum konfigurasi jaringan.', 'tersedia'],
            ['Lab Basis Data', 2, 36,
                'Ruang praktikum basis data dengan server lokal dan komputer yang terhubung jaringan LAN.', 'tersedia'],
            ['Lab Rekayasa Perangkat Lunak', 3, 32,
                'Ruang kerja kelompok dengan papan tulis besar dan layar presentasi.', 'tersedia'],
            ['Lab Multimedia', 3, 25,
                'Ruang produksi konten digital dengan komputer berspesifikasi tinggi dan peredam suara.', 'tersedia'],
            // Contoh status lain agar terlihat pada halaman admin
            ['Lab IoT', 3, 20,
                'Ruang praktikum mikrokontroler dan sensor. Sedang dalam perbaikan instalasi listrik.', 'perbaikan'],
            ['Lab Keamanan Siber', 1, 24,
                'Ruang praktikum keamanan jaringan. Belum dibuka untuk peminjaman.', 'nonaktif'],
        ];

        $rows = [];
        foreach ($ruang as [$nama, $lantai, $kapasitas, $deskripsi, $status]) {
            $rows[] = [
                'nama' => $nama,
                'lokasi' => $lokasi.$lantai,
                'kapasitas' => $kapasitas,
                'deskripsi' => $deskripsi,
                'foto' => null,
                'status' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('ruang')->insert($rows);
    }
}
