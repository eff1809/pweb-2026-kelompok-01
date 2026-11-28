<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/**
 * Foto Ruang/Alat disimpan di disk "public" (storage/app/public/{ruang|alat}).
 * Berkas lama otomatis dihapus saat foto diganti/dihapus atau saat data dihapus.
 */
trait PunyaFoto
{
    public static function bootPunyaFoto(): void
    {
        // Pada event "updated", getOriginal() masih berisi path foto sebelum diubah.
        static::updated(function (self $model): void {
            if ($model->wasChanged('foto')) {
                $model->hapusBerkasFoto($model->getOriginal('foto'));
            }
        });

        static::deleted(function (self $model): void {
            $model->hapusBerkasFoto($model->foto);
        });
    }

    /** URL foto untuk tag <img>; gambar placeholder jika belum ada foto. */
    public function fotoUrl(): string
    {
        return $this->foto
            ? asset('storage/'.$this->foto)
            : asset('images/placeholder-foto.svg');
    }

    protected function hapusBerkasFoto(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
