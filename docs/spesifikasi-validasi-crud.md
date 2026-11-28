# Spesifikasi Validasi, Form, dan Modul CRUD

Disusun oleh: Muh. Fauzan Dwi Riyan (Back end) — Minggu 13

Dokumen ini melengkapi [backend-design.md](backend-design.md). Alur status, matriks hak akses, dan route peminjaman mengikuti dokumen tersebut; di sini dirinci aturan bisnis, spesifikasi form, modul CRUD, dan masukan untuk ERD.

## 1. Aturan Bisnis

### 1.1 Ruang

| Kode | Aturan |
|---|---|
| R1 | Nama ruang unik. |
| R2 | Status ruang: `tersedia`, `perbaikan`, `nonaktif`. Hanya ruang `tersedia` yang muncul di form peminjaman dan dapat diajukan. |
| R3 | Ruang yang sudah punya riwayat peminjaman **tidak boleh dihapus**; Admin mengubah statusnya menjadi `nonaktif`. Ruang tanpa riwayat boleh dihapus setelah konfirmasi. |
| R4 | Jika status ruang diubah menjadi `perbaikan`/`nonaktif` sementara masih ada peminjaman `disetujui` di masa depan, tampilkan peringatan kepada Admin (perubahan tetap boleh). |

### 1.2 Alat

| Kode | Aturan |
|---|---|
| A1 | Kode alat unik (contoh: `MIK-001`, `PRJ-002`). |
| A2 | `jumlah_total` = jumlah unit yang **layak dipinjam**. Unit rusak tidak dihitung. |
| A3 | Kondisi alat: `baik`, `rusak_ringan`, `rusak_berat`. Alat `rusak_berat` tidak dapat dipinjam. |
| A4 | Alat yang sudah punya riwayat peminjaman tidak boleh dihapus (sama seperti R3). |
| A5 | `jumlah_total` tidak boleh diturunkan di bawah jumlah unit yang sedang berstatus `dipinjam`. |

### 1.3 Peminjaman

Aturan dasar (tanggal tidak lampau, jam selesai setelah jam mulai, maksimal 8 jam, bentrok, stok, status) sudah ada di [backend-design.md](backend-design.md#aturan-validasi-peminjaman). Tambahan:

| Kode | Aturan |
|---|---|
| P1 | Satu pengajuan minimal berisi **ruang atau setidaknya satu alat** (boleh keduanya). |
| P2 | Jika `tanggal` = hari ini, `jam_mulai` harus setelah jam sekarang. |
| P3 | Pengajuan paling jauh **30 hari** ke depan. *(usulan, perlu disepakati)* |
| P4 | Jam operasional laboratorium **07.00–18.00**. *(usulan, perlu disepakati)* |
| P5 | Satu alat tidak boleh muncul dua kali dalam satu pengajuan; jumlah per alat minimal 1. |
| P6 | Ruang yang dipilih harus berstatus `tersedia`; alat yang dipilih tidak boleh `rusak_berat`. |
| P7 | Cek bentrok dan stok diulang saat Admin menyetujui, di dalam transaksi database dengan `lockForUpdate()`, agar dua persetujuan bersamaan tidak lolos. |

### 1.4 Logika cek bentrok dan stok (rancangan kode)

Status yang dihitung: `disetujui` dan `dipinjam`. Dua rentang beririsan jika `mulai_baru < selesai_lama` dan `selesai_baru > mulai_lama` pada tanggal yang sama.

```php
// Bentrok ruang
$bentrok = Peminjaman::where('ruang_id', $ruangId)
    ->whereDate('tanggal', $tanggal)
    ->whereIn('status', ['disetujui', 'dipinjam'])
    ->where('jam_mulai', '<', $jamSelesai)
    ->where('jam_selesai', '>', $jamMulai)
    ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
    ->exists();

// Stok tersedia satu alat pada rentang tersebut
$terpakai = DB::table('peminjaman_alat')
    ->join('peminjaman', 'peminjaman.id', '=', 'peminjaman_alat.peminjaman_id')
    ->where('peminjaman_alat.alat_id', $alat->id)
    ->whereDate('peminjaman.tanggal', $tanggal)
    ->whereIn('peminjaman.status', ['disetujui', 'dipinjam'])
    ->where('peminjaman.jam_mulai', '<', $jamSelesai)
    ->where('peminjaman.jam_selesai', '>', $jamMulai)
    ->when($kecualiId, fn ($q) => $q->where('peminjaman.id', '!=', $kecualiId))
    ->sum('peminjaman_alat.jumlah');

$tersedia = $alat->jumlah_total - $terpakai;
```

Logika ini akan ditaruh di satu kelas (misalnya `app/Services/KetersediaanService.php`) agar dipakai bersama oleh Form Request pengajuan (Fauzan) dan aksi persetujuan Admin (Sabrun).

## 2. Spesifikasi Form

Semua validasi dilakukan di Form Request (`app/Http/Requests/`). Pesan error berbahasa Indonesia dan ditampilkan di bawah isian terkait (`@error('field')`). Nilai lama dipertahankan dengan `old()`.

### 2.1 Form Ruang (Admin) — `RuangRequest`

| Field | Input | Aturan Laravel | Contoh pesan error |
|---|---|---|---|
| `nama` | text | `required\|string\|max:100\|unique:ruang,nama` (abaikan id sendiri saat ubah) | Nama ruang wajib diisi. / Nama ruang sudah digunakan. |
| `lokasi` | text | `required\|string\|max:150` | Lokasi wajib diisi. |
| `kapasitas` | number | `required\|integer\|min:1\|max:500` | Kapasitas minimal 1 orang. |
| `deskripsi` | textarea | `nullable\|string\|max:1000` | Deskripsi maksimal 1000 karakter. |
| `foto` | file | `nullable\|image\|mimes:jpg,jpeg,png,webp\|max:2048` | Foto harus berupa gambar JPG, PNG, atau WEBP. / Ukuran foto maksimal 2 MB. |
| `status` | select | `required\|in:tersedia,perbaikan,nonaktif` | Status ruang tidak valid. |

### 2.2 Form Alat (Admin) — `AlatRequest`

| Field | Input | Aturan Laravel | Contoh pesan error |
|---|---|---|---|
| `nama` | text | `required\|string\|max:100` | Nama alat wajib diisi. |
| `kode` | text | `required\|string\|max:20\|alpha_dash\|unique:alat,kode` (abaikan id sendiri saat ubah) | Kode alat sudah digunakan. / Kode hanya boleh huruf, angka, dan tanda hubung. |
| `kategori` | select/text | `required\|string\|max:50` (atau `exists:kategori_alat,id` jika tabel kategori dipakai) | Kategori wajib dipilih. |
| `jumlah_total` | number | `required\|integer\|min:1\|max:1000` + aturan A5 saat ubah | Jumlah minimal 1. / Jumlah tidak boleh kurang dari unit yang sedang dipinjam (:n unit). |
| `kondisi` | select | `required\|in:baik,rusak_ringan,rusak_berat` | Kondisi alat tidak valid. |
| `foto` | file | sama dengan foto ruang | sama dengan foto ruang |

### 2.3 Form Peminjaman (Mahasiswa) — `PeminjamanRequest`

Dipakai untuk membuat dan mengubah pengajuan. Bentuk data alat dari form (disepakati dengan Tri):

```html
<select name="alat[0][alat_id]">...</select>
<input type="number" name="alat[0][jumlah]">
<!-- baris berikutnya: alat[1][alat_id], alat[1][jumlah], dst. -->
```

| Field | Input | Aturan Laravel | Contoh pesan error |
|---|---|---|---|
| `ruang_id` | select (ruang `tersedia`) | `nullable\|required_without:alat\|exists:ruang,id` | Pilih ruang atau minimal satu alat. |
| `tanggal` | date | `required\|date\|after_or_equal:today\|before_or_equal:+30 days` | Tanggal tidak boleh sebelum hari ini. / Pengajuan paling lambat 30 hari ke depan. |
| `jam_mulai` | time | `required\|date_format:H:i` | Jam mulai wajib diisi. |
| `jam_selesai` | time | `required\|date_format:H:i\|after:jam_mulai` | Jam selesai harus setelah jam mulai. |
| `keperluan` | textarea | `required\|string\|min:10\|max:500` | Keperluan minimal 10 karakter. |
| `alat` | array | `nullable\|array\|required_without:ruang_id` | Pilih ruang atau minimal satu alat. |
| `alat.*.alat_id` | select | `required\|distinct\|exists:alat,id` | Alat yang sama dipilih lebih dari sekali. |
| `alat.*.jumlah` | number | `required\|integer\|min:1` | Jumlah alat minimal 1. |

Validasi tambahan di `after()` pada Form Request (setelah aturan dasar lolos):

| Cek | Field yang diberi error | Pesan |
|---|---|---|
| Durasi > 8 jam | `jam_selesai` | Durasi peminjaman maksimal 8 jam. |
| Di luar 07.00–18.00 | `jam_mulai` / `jam_selesai` | Peminjaman hanya pada jam operasional 07.00–18.00. |
| Hari ini, jam sudah lewat (P2) | `jam_mulai` | Jam mulai sudah lewat. |
| Ruang tidak `tersedia` (P6) | `ruang_id` | Ruang sedang tidak dapat dipinjam. |
| Ruang bentrok | `ruang_id` | Ruang sudah dipakai pada :tanggal pukul :mulai–:selesai. |
| Alat `rusak_berat` (P6) | `alat.N.alat_id` | :alat sedang rusak dan tidak dapat dipinjam. |
| Stok kurang | `alat.N.jumlah` | Stok :alat hanya tersedia :n unit pada jam tersebut. |

### 2.4 Form Persetujuan (Admin)

| Aksi | Field | Aturan | Pesan |
|---|---|---|---|
| Setujui | `catatan_admin` | `nullable\|string\|max:500` | — |
| Tolak | `catatan_admin` | `required\|string\|min:5\|max:500` | Alasan penolakan wajib diisi. |

Saat menyetujui, cek bentrok dan stok diulang (P7). Jika gagal, persetujuan dibatalkan dengan pesan flash: "Tidak dapat disetujui: ruang/alat sudah terpakai oleh peminjaman lain."

### 2.5 Pencarian, Filter, dan Pagination (query string, metode GET)

| Halaman | Parameter | Keterangan |
|---|---|---|
| Ruang | `q`, `status` | `q` mencari di nama dan lokasi |
| Alat | `q`, `kategori`, `kondisi` | `q` mencari di nama dan kode |
| Peminjaman (Admin) | `q`, `status`, `dari`, `sampai` | `q` mencari di nama/NIM mahasiswa dan keperluan |
| Peminjaman (Mahasiswa) | `status` | hanya data milik sendiri |

Pagination 10 data per halaman dengan `->paginate(10)->withQueryString()` agar filter tetap terbawa saat pindah halaman. Parameter yang tidak valid diabaikan, tidak memunculkan error. Isi kembali form filter dari `$filter` (nilainya `null` jika tidak dipakai), misalnya `value="{{ $filter['q'] }}"`, dan tampilkan link halaman dengan `{{ $ruang->links() }}`.

## 3. Modul CRUD dan Halaman

Nama tabel memakai bahasa Indonesia tunggal (`ruang`, `alat`, `peminjaman`), sehingga setiap model wajib menulis `protected $table = '...'`; tanpa itu Laravel mencari tabel `ruangs`/`alats`.

### 3.1 Ruang

| Halaman | Route | Nama route | Akses | View |
|---|---|---|---|---|
| Daftar + cari + pagination | `GET /admin/ruang` | `admin.ruang.index` | Admin | `admin/ruang/index` |
| Tambah | `GET /admin/ruang/create`, `POST /admin/ruang` | `admin.ruang.create`, `.store` | Admin | `admin/ruang/create` |
| Detail | `GET /admin/ruang/{ruang}` | `admin.ruang.show` | Admin | `admin/ruang/show` |
| Ubah | `GET /admin/ruang/{ruang}/edit`, `PUT /admin/ruang/{ruang}` | `admin.ruang.edit`, `.update` | Admin | `admin/ruang/edit` |
| Hapus (modal konfirmasi) | `DELETE /admin/ruang/{ruang}` | `admin.ruang.destroy` | Admin | — |
| Katalog | `GET /ruang` | `ruang.index` | Login | `ruang/index` |
| Detail katalog | `GET /ruang/{ruang}` | `ruang.show` | Login | `ruang/show` |

Controller: `Admin\RuangController` (resource) dan `RuangController` (katalog: index, show).

### 3.2 Alat

Sama dengan Ruang: `admin.alat.*` (resource, controller `Admin\AlatController`) dan katalog `alat.index`, `alat.show` (controller `AlatController`). View di `admin/alat/` dan `alat/`.

### 3.3 Peminjaman

Mengikuti route di [backend-design.md](backend-design.md#usulan-route-utama). Ringkasan halaman:

| Halaman | Peran | Pemilik fitur |
|---|---|---|
| Riwayat (index + filter status) | Mahasiswa | Tri (view), Sabrun (alur) |
| Form ajukan / ubah | Mahasiswa | Tri (view), Fauzan (validasi), Sabrun (alur) |
| Detail + tombol batal | Mahasiswa | Tri, Sabrun |
| Daftar semua + cari/filter + pagination | Admin | Nurul (view), Fauzan (filter), Sabrun (alur) |
| Detail + setujui/tolak/serahkan/kembalikan | Admin | Nurul (view), Sabrun (alur) |

### 3.4 Data yang dikirim controller ke view (kontrak dengan Tri dan Nurul)

| View | Variabel |
|---|---|
| `*/ruang/index` | `$ruang` (paginator), `$filter` (array `q`, `status`), `$daftarStatus` (pilihan filter; katalog tanpa `nonaktif`) |
| `*/ruang/show`, `admin/ruang/edit` | `$ruang` (model) |
| `*/alat/index` | `$alat` (paginator), `$filter` (array `q`, `kategori`, `kondisi`), `$daftarKategori` (kategori yang sudah dipakai), `$daftarKondisi` |
| `*/alat/show`, `admin/alat/edit` | `$alat` (model) |
| Form peminjaman | `$ruang` (koleksi ruang `tersedia`), `$alat` (koleksi alat selain `rusak_berat`), `$peminjaman` (hanya saat ubah) |

Foto disimpan di disk `public` (`storage/app/public/ruang`, `.../alat`) dan ditampilkan dengan `asset('storage/'.$ruang->foto)`; jika kosong, tampilkan gambar placeholder. Foto lama dihapus saat diganti atau saat data dihapus.

## 4. Masukan untuk ERD (untuk Ainun)

| No | Tabel | Masukan | Alasan |
|---|---|---|---|
| 1 | `ruang` | `nama` unique; `status` enum `tersedia/perbaikan/nonaktif` default `tersedia`; `foto` nullable | R1, R2 |
| 2 | `alat` | `kode` unique; `kondisi` enum `baik/rusak_ringan/rusak_berat`; `jumlah_total` unsigned int; `foto` nullable | A1–A3 |
| 3 | `alat` | Putuskan `kategori` sebagai string biasa atau tabel `kategori_alat`. Usul: **string** agar sederhana; tabel kategori hanya jika ingin CRUD entitas tambahan. | Mengurangi pekerjaan Sprint 1 |
| 4 | `users` | `nim` nullable (Admin tidak punya NIM) dan unique; `peran` enum `admin/mahasiswa` default `mahasiswa` | Registrasi publik hanya Mahasiswa |
| 5 | `peminjaman` | `ruang_id` nullable, FK `restrictOnDelete`; `tanggal` date; `jam_mulai`/`jam_selesai` time; `status` enum 6 nilai default `diajukan`; `catatan_admin` nullable | P1, R3 |
| 6 | `peminjaman` | Index gabungan `(ruang_id, tanggal, status)` | Mempercepat cek bentrok |
| 7 | `peminjaman` | Usulan kolom `disetujui_oleh` (FK users, nullable), `diserahkan_pada`, `dikembalikan_pada` (datetime, nullable) | Pencatatan penyerahan/pengembalian dan bahan rekap PDF; opsional |
| 8 | `peminjaman_alat` | Unique `(peminjaman_id, alat_id)`; FK `peminjaman_id` `cascadeOnDelete`, FK `alat_id` `restrictOnDelete`; `jumlah` unsigned int min 1 | P5, A4 |

## 5. Hal yang Perlu Disepakati di Rapat

1. Batas pengajuan 30 hari ke depan (P3) dan jam operasional 07.00–18.00 (P4).
2. Kategori alat: string atau tabel tersendiri.
3. Bentuk field alat di form peminjaman (`alat[i][alat_id]`, `alat[i][jumlah]`) dengan Tri.
4. Nama variabel view (Bagian 3.4) dengan Tri dan Nurul.
