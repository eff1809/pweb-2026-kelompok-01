<?php

namespace App\Models;

use Database\Factories\PeminjamanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Peminjaman extends Model
{
    /** @use HasFactory<PeminjamanFactory> */
    use HasFactory;

    // Nama tabel bahasa Indonesia tunggal; tanpa ini Laravel mencari "peminjamen".
    protected $table = 'peminjaman';

    public const STATUS_DIAJUKAN = 'diajukan';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_DIPINJAM = 'dipinjam';

    public const STATUS_DIKEMBALIKAN = 'dikembalikan';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    /** Pilihan status untuk filter dan badge: nilai => label. */
    public const DAFTAR_STATUS = [
        self::STATUS_DIAJUKAN => 'Diajukan',
        self::STATUS_DISETUJUI => 'Disetujui',
        self::STATUS_DITOLAK => 'Ditolak',
        self::STATUS_DIPINJAM => 'Dipinjam',
        self::STATUS_DIKEMBALIKAN => 'Dikembalikan',
        self::STATUS_DIBATALKAN => 'Dibatalkan',
    ];

    /** Status yang masih menahan ketersediaan ruang dan stok alat. */
    public const STATUS_AKTIF = [
        self::STATUS_DISETUJUI,
        self::STATUS_DIPINJAM,
    ];

    protected $fillable = [
        'user_id',
        'ruang_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'keperluan',
        'status',
        'catatan_admin',
        'diproses_oleh',
        'diproses_pada',
        'dipinjam_pada',
        'dikembalikan_pada',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'diproses_pada' => 'datetime',
            'dipinjam_pada' => 'datetime',
            'dikembalikan_pada' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ruang(): BelongsTo
    {
        return $this->belongsTo(Ruang::class);
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function alat(): BelongsToMany
    {
        return $this->belongsToMany(Alat::class, 'peminjaman_alat')
            ->withPivot('jumlah');
    }

    public function isDiajukan(): bool
    {
        return $this->status === self::STATUS_DIAJUKAN;
    }

    public function isDisetujui(): bool
    {
        return $this->status === self::STATUS_DISETUJUI;
    }

    public function isDipinjam(): bool
    {
        return $this->status === self::STATUS_DIPINJAM;
    }

    public function labelStatus(): string
    {
        return self::DAFTAR_STATUS[$this->status] ?? $this->status;
    }
}
