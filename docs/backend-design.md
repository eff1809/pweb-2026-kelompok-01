# Rancangan Backend

## Use case

### Mahasiswa
- Login dan melihat katalog ruang serta alat.
- Mengajukan peminjaman.
- Melihat status dan riwayat peminjaman miliknya.
- Mengubah pengajuan miliknya selama status masih `diajukan`.
- Membatalkan pengajuan miliknya selama status masih `diajukan`.

### Admin Lab
- Melihat seluruh pengajuan.
- Menyetujui atau menolak pengajuan dengan catatan.
- Mencatat penyerahan dan pengembalian.
- Mengelola ruang dan alat bersama anggota yang menangani CRUD-nya.

## Alur status

`diajukan` → `disetujui` atau `ditolak`

Pengajuan yang disetujui berlanjut menjadi:

`disetujui` → `dipinjam` → `dikembalikan`

Mahasiswa dapat membatalkan pengajuan miliknya hanya saat status `diajukan`. Pembatalan mengubah status menjadi `dibatalkan`; data tetap disimpan sebagai riwayat. Status `ditolak` dan `dibatalkan` mengakhiri pengajuan.

## Matriks hak akses

| Aksi | Mahasiswa | Admin Lab |
|---|---|---|
| Melihat katalog ruang dan alat | Ya | Ya |
| Mengajukan peminjaman | Ya | Tidak |
| Melihat riwayat peminjaman | Miliknya sendiri | Semua |
| Mengubah pengajuan | Miliknya, saat `diajukan` | Tidak |
| Membatalkan pengajuan | Miliknya, saat `diajukan` | Tidak |
| Menyetujui atau menolak | Tidak | Ya |
| Mencatat pengembalian | Tidak | Ya |
| CRUD ruang dan alat | Tidak | Ya, dikoordinasikan dengan Fauzan |

Pemeriksaan peran, status, dan kepemilikan dilakukan di sisi server menggunakan middleware, Gate, atau Policy.

## Aturan validasi peminjaman

- Tanggal peminjaman tidak boleh lampau.
- Waktu selesai harus setelah waktu mulai.
- Pengajuan berlangsung pada tanggal yang sama.
- Durasi maksimal adalah 8 jam per pengajuan.
- Jadwal ruang tidak boleh bentrok.
- Jumlah alat yang diminta tidak boleh melebihi stok tersedia.
- Mahasiswa hanya boleh mengubah atau membatalkan peminjamannya sendiri saat statusnya `diajukan`.
- Admin hanya dapat menyetujui atau menolak pengajuan berstatus `diajukan`.
- Penyerahan (`disetujui` → `dipinjam`) dicatat Admin untuk peminjaman berstatus `disetujui`.
- Pengembalian dicatat untuk peminjaman berstatus `dipinjam`.
- Catatan Admin wajib diisi saat menolak, opsional saat menyetujui.

### Status yang dihitung saat cek bentrok dan stok

- Ruang dianggap terpakai, dan alat dianggap keluar dari stok, oleh peminjaman berstatus `disetujui` atau `dipinjam` yang jamnya beririsan.
- Dua rentang waktu beririsan jika `mulai_baru < selesai_lama` dan `selesai_baru > mulai_lama` pada tanggal yang sama. Rentang yang hanya bersentuhan (misalnya 08.00–10.00 dan 10.00–12.00) tidak dianggap bentrok.
- Stok tersedia = `alat.jumlah_total` − jumlah alat pada peminjaman `disetujui`/`dipinjam` yang beririsan.
- Pemeriksaan dilakukan saat Mahasiswa mengajukan atau mengubah pengajuan, lalu diulang saat Admin menyetujui, karena beberapa pengajuan `diajukan` bisa memperebutkan slot yang sama.
- Saat mengubah pengajuan, peminjaman itu sendiri dikecualikan dari pemeriksaan.

## Usulan route utama

| Method | URL | Akses | Tujuan |
|---|---|---|---|
| `GET` | `/dashboard` | Pengguna login | Dashboard sesuai peran |
| `GET` | `/ruang`, `/alat` | Mahasiswa/Admin | Melihat katalog |
| `GET` | `/peminjaman` | Mahasiswa | Melihat riwayat sendiri |
| `POST` | `/peminjaman` | Mahasiswa | Membuat pengajuan |
| `GET` | `/peminjaman/{peminjaman}` | Pemilik/Admin | Melihat detail |
| `PUT` | `/peminjaman/{peminjaman}` | Pemilik, status `diajukan` | Mengubah pengajuan |
| `PATCH` | `/peminjaman/{peminjaman}/batal` | Pemilik, status `diajukan` | Membatalkan pengajuan |
| `GET` | `/admin/peminjaman` | Admin | Melihat semua pengajuan |
| `PATCH` | `/admin/peminjaman/{peminjaman}/setujui` | Admin | Menyetujui pengajuan |
| `PATCH` | `/admin/peminjaman/{peminjaman}/tolak` | Admin | Menolak dengan catatan |
| `PATCH` | `/admin/peminjaman/{peminjaman}/serahkan` | Admin | Mencatat penyerahan (`disetujui` → `dipinjam`) |
| `PATCH` | `/admin/peminjaman/{peminjaman}/kembalikan` | Admin | Mencatat pengembalian |
| `GET` | `/admin/laporan/peminjaman/pdf` | Admin | Mengekspor rekap PDF |

Route final harus disesuaikan dengan ERD, implementasi Laravel, serta kebutuhan halaman frontend.

## Konvensi folder

- `app/Http/Controllers/Admin/` — aksi Admin.
- `app/Http/Controllers/Mahasiswa/` — aksi Mahasiswa.
- `app/Http/Requests/` — validasi form.
- `app/Policies/` — otorisasi peran dan kepemilikan.
- `routes/web.php` — route web.
- `tests/Feature/` — pengujian autentikasi dan alur peminjaman.

## Panduan setup lokal (bahan README)

### Kebutuhan

| Perangkat | Versi minimal | Catatan |
|---|---|---|
| PHP | 8.2 | Ekstensi `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` (untuk dompdf) aktif |
| Composer | 2.x | |
| Node.js | 20 LTS | Untuk Vite dan Tailwind |
| MySQL / MariaDB | 8.0 / 10.6 | Laragon atau XAMPP sudah cukup |
| Git | 2.x | |

Cek versi:

```bash
php -v
composer -V
node -v
mysql --version
```

### Langkah instalasi

```bash
git clone https://github.com/eff1809/pweb-2026-kelompok-01.git
cd pweb-2026-kelompok-01
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Buat database kosong `pweb_kelompok01`, lalu sesuaikan `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pweb_kelompok01
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migration, seeder, dan aplikasi:

```bash
php artisan migrate --seed
php artisan storage:link
npm run dev          # terminal 1
php artisan serve    # terminal 2
```

Aplikasi dapat dibuka di `http://127.0.0.1:8000`.

### Masalah yang sering muncul

- `could not find driver`: aktifkan `extension=pdo_mysql` di `php.ini`.
- `Vite manifest not found`: jalankan `npm run dev` atau `npm run build`.
- Foto tidak tampil: jalankan `php artisan storage:link`.
- Setelah menarik `main` yang mengubah migration: `php artisan migrate:fresh --seed` (menghapus data lokal).