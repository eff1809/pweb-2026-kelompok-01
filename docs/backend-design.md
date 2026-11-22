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
- Pengembalian dicatat untuk peminjaman berstatus `dipinjam`.

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