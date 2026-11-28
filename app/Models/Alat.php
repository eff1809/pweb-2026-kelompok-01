<?php

namespace App\Models;

use Database\Factories\AlatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Alat extends Model
{
    /** @use HasFactory<AlatFactory> */
    use HasFactory;

    // Nama tabel bahasa Indonesia tunggal; tanpa ini Laravel mencari "alats".
    protected $table = 'alat';

    public const KONDISI_BAIK = 'baik';

    public const KONDISI_RUSAK_RINGAN = 'rusak_ringan';

    public const KONDISI_RUSAK_BERAT = 'rusak_berat';

    /** Pilihan kondisi untuk form dan filter: nilai => label. */
    public const DAFTAR_KONDISI = [
        self::KONDISI_BAIK => 'Baik',
        self::KONDISI_RUSAK_RINGAN => 'Rusak ringan',
        self::KONDISI_RUSAK_BERAT => 'Rusak berat',
    ];

    protected $fillable = [
        'nama',
        'kode',
        'kategori',
        'jumlah_total',
        'kondisi',
        'foto',
    ];

    protected function casts(): array
    {
        return [
            'jumlah_total' => 'integer',
        ];
    }

    /** Alat rusak berat tidak dapat dipinjam (aturan A3). */
    public function bisaDipinjam(): bool
    {
        return $this->kondisi !== self::KONDISI_RUSAK_BERAT;
    }

    public function labelKondisi(): string
    {
        return self::DAFTAR_KONDISI[$this->kondisi] ?? $this->kondisi;
    }

    public function peminjaman(): BelongsToMany
    {
        return $this->belongsToMany(Peminjaman::class, 'peminjaman_alat')
            ->withPivot('jumlah');
    }
}
