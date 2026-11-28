<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PeminjamanSeeder extends Seeder
{
    /**
     * Tanggal dibuat relatif terhadap hari ini supaya data demo selalu terlihat
     * baru kapan pun php artisan migrate:fresh --seed dijalankan.
     */
    public function run(): void
    {
        $mahasiswa = User::where('peran', 'mahasiswa')->orderBy('id')->get();
        $admin = User::where('peran', 'admin')->orderBy('id')->first();

        if ($mahasiswa->isEmpty() || ! $admin) {
            $this->command?->warn('PeminjamanSeeder dilewati: UserSeeder harus membuat minimal 1 admin dan 1 mahasiswa.');

            return;
        }

        $ruangId = DB::table('ruang')->pluck('id', 'nama');
        $alatId = DB::table('alat')->pluck('id', 'kode');

        // [indeks mahasiswa, ruang|null, hari ke- (0 = hari ini), jam mulai, jam selesai,
        //  keperluan, status, catatan admin, [kode alat => jumlah]]
        $data = [
            // ---- Sudah dikembalikan (riwayat) ----
            [0, 'Lab Jaringan Komputer', -14, '09:00', '12:00',
                'Praktikum konfigurasi routing statis', 'dikembalikan', null,
                ['NET-001' => 2, 'NET-002' => 1]],
            [1, 'Lab Pemrograman Dasar', -12, '13:00', '16:00',
                'Kerja kelompok tugas besar Algoritma dan Pemrograman', 'dikembalikan', null, []],
            [2, null, -10, '08:00', '16:00',
                'Pengambilan data sensor untuk penelitian tugas akhir', 'dikembalikan', null,
                ['MCU-001' => 2, 'MCU-004' => 4, 'MCU-005' => 2]],
            [3, 'Lab Multimedia', -9, '10:00', '13:00',
                'Pengambilan gambar dan penyuntingan video profil himpunan', 'dikembalikan', null,
                ['KOM-001' => 2]],
            [4, 'Lab Basis Data', -7, '08:00', '10:30',
                'Latihan query SQL untuk persiapan ujian praktikum', 'dikembalikan', null, []],
            [5, null, -6, '09:00', '16:00',
                'Pengukuran rangkaian untuk proyek mata kuliah Elektronika', 'dikembalikan', null,
                ['INS-001' => 3, 'INS-002' => 1, 'INS-003' => 1]],

            // ---- Ditolak ----
            [7, 'Lab Jaringan Komputer', -4, '08:00', '12:00',
                'Latihan mandiri konfigurasi VLAN', 'ditolak',
                'Ruang dipakai untuk praktikum Jaringan Komputer pada jam tersebut. Silakan ajukan di jadwal lain.',
                []],

            // ---- Dibatalkan oleh mahasiswa ----
            [8, 'Lab Basis Data', 2, '10:00', '12:00',
                'Konsultasi proyek basis data kelompok', 'dibatalkan', null, []],

            // ---- Sedang dipinjam (hari ini) ----
            [6, 'Lab Pemrograman Dasar', 0, '08:00', '16:00',
                'Pelatihan pemrograman untuk anggota himpunan', 'dipinjam', null,
                ['PRS-001' => 1]],
            [8, null, 0, '08:00', '16:00',
                'Perakitan robot line follower untuk persiapan lomba', 'dipinjam', null,
                ['MCU-001' => 4, 'MCU-005' => 3, 'INS-001' => 2, 'INS-004' => 2]],

            // ---- Disetujui (akan datang) ----
            [0, 'Lab Pemrograman Dasar', 1, '13:00', '16:00',
                'Latihan lomba pemrograman kompetitif', 'disetujui',
                'Disetujui. Harap merapikan ruangan setelah digunakan.',
                ['KOM-001' => 5]],
            [1, 'Lab Multimedia', 2, '09:00', '12:00',
                'Produksi konten dokumentasi kegiatan seminar jurusan', 'disetujui', null,
                ['PRS-001' => 1, 'KOM-001' => 2]],
            [8, null, 3, '13:00', '16:00',
                'Uji coba prototipe smart home berbasis ESP32', 'disetujui', null,
                ['MCU-002' => 3, 'MCU-004' => 2]],

            // ---- Menunggu persetujuan ----
            [8, 'Lab Pemrograman Dasar', 6, '09:00', '11:00',
                'Asistensi tugas pemrograman bersama teman satu kelas', 'diajukan', null, []],
            [8, 'Lab Multimedia', 7, '13:00', '16:00',
                'Gladi presentasi proyek akhir semester', 'diajukan', null,
                ['PRS-001' => 1, 'PRS-002' => 1]],
        ];

        foreach ($data as [$idx, $ruang, $hari, $mulai, $selesai, $keperluan, $status, $catatan, $alat]) {
            $tanggal = Carbon::today()->addDays($hari);

            // Waktu pengajuan selalu di masa lalu dan sebelum waktu diproses
            $dibuat = Carbon::today()->addDays(min($hari - 3, -1))->setTime(10, 0);
            $diproses = in_array($status, ['disetujui', 'ditolak', 'dipinjam', 'dikembalikan'], true);
            $sudahDipinjam = in_array($status, ['dipinjam', 'dikembalikan'], true);

            $peminjamanId = DB::table('peminjaman')->insertGetId([
                'user_id' => $mahasiswa[$idx % $mahasiswa->count()]->id,
                'ruang_id' => $ruang ? $ruangId[$ruang] : null,
                'tanggal' => $tanggal->toDateString(),
                'jam_mulai' => $mulai.':00',
                'jam_selesai' => $selesai.':00',
                'keperluan' => $keperluan,
                'status' => $status,
                'catatan_admin' => $catatan,
                'diproses_oleh' => $diproses ? $admin->id : null,
                'diproses_pada' => $diproses ? $dibuat->copy()->addHours(4) : null,
                'dipinjam_pada' => $sudahDipinjam ? $this->jam($tanggal, $mulai, -5) : null,
                'dikembalikan_pada' => $status === 'dikembalikan' ? $this->jam($tanggal, $selesai, 5) : null,
                'created_at' => $dibuat,
                'updated_at' => $dibuat,
            ]);

            foreach ($alat as $kode => $jumlah) {
                DB::table('peminjaman_alat')->insert([
                    'peminjaman_id' => $peminjamanId,
                    'alat_id' => $alatId[$kode],
                    'jumlah' => $jumlah,
                ]);
            }
        }
    }

    /** Tanggal tertentu pada jam "HH:MM" ditambah beberapa menit. */
    private function jam(Carbon $tanggal, string $jam, int $tambahMenit = 0): Carbon
    {
        [$h, $m] = explode(':', $jam);

        return $tanggal->copy()->setTime((int) $h, (int) $m)->addMinutes($tambahMenit);
    }
}
