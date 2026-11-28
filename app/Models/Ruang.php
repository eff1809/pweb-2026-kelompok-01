<?php

namespace App\Models;

use App\Models\Concerns\PunyaFoto;
use Database\Factories\RuangFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ruang extends Model
{
    /** @use HasFactory<RuangFactory> */
    use HasFactory, PunyaFoto;

    // Nama tabel bahasa Indonesia tunggal; tanpa ini Laravel mencari "ruangs".
    protected $table = 'ruang';

    public const STATUS_TERSEDIA = 'tersedia';

    public const STATUS_PERBAIKAN = 'perbaikan';

    public const STATUS_NONAKTIF = 'nonaktif';

    /** Pilihan status untuk form dan filter: nilai => label. */
    public const DAFTAR_STATUS = [
        self::STATUS_TERSEDIA => 'Tersedia',
        self::STATUS_PERBAIKAN => 'Perbaikan',
        self::STATUS_NONAKTIF => 'Nonaktif',
    ];

    protected $fillable = [
        'nama',
        'lokasi',
        'kapasitas',
        'deskripsi',
        'foto',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'kapasitas' => 'integer',
        ];
    }

    public function isTersedia(): bool
    {
        return $this->status === self::STATUS_TERSEDIA;
    }

    public function labelStatus(): string
    {
        return self::DAFTAR_STATUS[$this->status] ?? $this->status;
    }

    /** Pencarian `q` di nama dan lokasi ruang; kata kosong tidak memfilter apa pun. */
    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        if ($kata === null || $kata === '') {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($kata): void {
            $sub->where('nama', 'like', "%{$kata}%")
                ->orWhere('lokasi', 'like', "%{$kata}%");
        });
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(Peminjaman::class);
    }
}
