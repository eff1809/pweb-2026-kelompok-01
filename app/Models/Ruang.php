<?php

namespace App\Models;

use Database\Factories\RuangFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruang extends Model
{
    /** @use HasFactory<RuangFactory> */
    use HasFactory;

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
}
