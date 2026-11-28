<?php

namespace App\Models;

use App\Models\Concerns\PunyaFoto;
use Database\Factories\AlatFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Alat extends Model
{
    /** @use HasFactory<AlatFactory> */
    use HasFactory, PunyaFoto;

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

    /** Pencarian `q` di nama dan kode alat; kata kosong tidak memfilter apa pun. */
    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        if ($kata === null || $kata === '') {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($kata): void {
            $sub->where('nama', 'like', "%{$kata}%")
                ->orWhere('kode', 'like', "%{$kata}%");
        });
    }

    /** Kategori yang sudah dipakai, untuk pilihan filter dan saran isian form. */
    public static function daftarKategori(): array
    {
        return static::query()->distinct()->orderBy('kategori')->pluck('kategori')->all();
    }

    public function peminjaman(): BelongsToMany
    {
        return $this->belongsToMany(Peminjaman::class, 'peminjaman_alat')
            ->withPivot('jumlah');
    }

    /** Jumlah unit alat ini yang sedang berstatus dipinjam (aturan A5). */
    public function jumlahSedangDipinjam(): int
    {
        return (int) $this->peminjaman()
            ->where('peminjaman.status', Peminjaman::STATUS_DIPINJAM)
            ->sum('peminjaman_alat.jumlah');
    }
}
