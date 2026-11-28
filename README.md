# LABORA — Sistem Peminjaman Ruang dan Alat Laboratorium

Repositori proyek kelompok mata kuliah **Pemrograman Web** (Program Studi Teknik Informatika, Universitas Hasanuddin) — **Kelompok 01**.

LABORA adalah aplikasi web berbasis **Laravel 12** untuk mengelola inventaris dan pengajuan peminjaman ruang serta alat laboratorium dengan dua peran pengguna: **Admin Laboratorium** dan **Mahasiswa**.

---

## Fitur Utama

- **Autentikasi & Peran:** Registrasi mahasiswa (dengan validasi NIM), login, logout, dan pembatasan akses per peran (`admin` / `mahasiswa`).
- **Manajemen Ruang & Alat (Admin Lab):** CRUD ruang dan alat laboratorium beserta validasi server berbahasa Indonesia, katalog ruang/alat, pencarian, filter, dan pagination.
- **Alur Peminjaman (Mahasiswa):** Pengajuan peminjaman ruang dan/atau alat, cek bentrok jadwal ruang dan ketersediaan stok alat secara otomatis, ubah dan batalkan pengajuan selama masih berstatus `diajukan`, serta riwayat peminjaman.
- **Persetujuan & Pengembalian (Admin Lab):** Daftar permintaan peminjaman, persetujuan/penolakan (dengan catatan wajib saat ditolak), pencatatan penyerahan (`dipinjam`), dan pengembalian (`dikembalikan`).
- **Fitur Nilai Tambah:** Ekspor PDF rekap peminjaman (`barryvdh/laravel-dompdf`), unggah foto ruang & alat, dan pengujian otomatis (PHPUnit).

---

## Teknologi & Pustaka Utama

| Komponen | Teknologi / Paket |
| --- | --- |
| Backend | PHP `^8.2`, Laravel `^12.0` |
| Autentikasi | Laravel Breeze `^2.4` |
| Frontend | Blade, Tailwind CSS, Alpine.js, Vite |
| Basis Data | MySQL / MariaDB (SQLite in-memory untuk pengujian) |
| Ekspor PDF | `barryvdh/laravel-dompdf` |
| Pengujian & Linter | PHPUnit `^11.5`, Laravel Pint `^1.13` |

---

## Prasyarat

Pastikan perangkat sudah terpasang:

- **PHP** >= 8.2 (dengan ekstensi standar Laravel: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `gd`/`fileinfo`)
- **Composer** >= 2.x
- **Node.js** >= 18.x dan **npm**
- **MySQL** atau **MariaDB**

---

## Panduan Instalasi & Setup Lokal

1. **Clone repositori dan masuk ke direktori proyek:**
   ```bash
   git clone https://github.com/eff1809/pweb-2026-kelompok-01.git
   cd pweb-2026-kelompok-01
   ```

2. **Pasang dependensi PHP dan JavaScript:**
   ```bash
   composer install
   npm install
   ```

3. **Salin berkas konfigurasi lingkungan dan buat `APP_KEY`:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi basis data di `.env`:**
   Buat database baru di MySQL/MariaDB (default di `.env.example`: `pweb_kelompok01`), lalu sesuaikan kredensial bila diperlukan:
   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=pweb_kelompok01
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Jalankan migrasi dan seeder data contoh:**
   ```bash
   php artisan migrate:fresh --seed
   ```

6. **Buat symlink storage (untuk foto ruang dan alat):**
   ```bash
   php artisan storage:link
   ```

7. **Jalankan server pengembangan:**
   - **Cara cepat (Laravel + Vite sekaligus):**
     ```bash
     composer run dev
     ```
   - **Atau jalankan di dua terminal terpisah:**
     ```bash
     php artisan serve
     npm run dev
     ```

   Aplikasi dapat diakses di `http://127.0.0.1:8000`.

---

## Akun Contoh (Hasil Seeder)

Perintah `php artisan migrate:fresh --seed` membuat akun berikut beserta 8 akun mahasiswa tambahan, 7 ruang lab, 16 alat lab, dan 18 contoh transaksi peminjaman:

| Peran | Email | Password | NIM |
| --- | --- | --- | --- |
| Admin Laboratorium | `admin@lab.test` | `password` | — |
| Mahasiswa | `mahasiswa@lab.test` | `password` | `D121241001` |

---

## Pengujian & Gaya Kode

- **Jalankan seluruh feature test:**
  ```bash
  php artisan test
  ```
- **Periksa format kode dengan Laravel Pint:**
  ```bash
  vendor/bin/pint --test
  ```
  Untuk merapikan otomatis: `vendor/bin/pint`.

---

## Dokumentasi Teknis

- [docs/backend-design.md](docs/backend-design.md) — Rancangan backend, peran, dan alur peminjaman.
- [docs/spesifikasi-validasi-crud.md](docs/spesifikasi-validasi-crud.md) — Spesifikasi aturan bisnis, validasi form, dan modul CRUD.
