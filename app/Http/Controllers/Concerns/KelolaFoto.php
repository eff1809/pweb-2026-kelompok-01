<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mengubah input form (foto + centang hapus_foto) menjadi data siap simpan.
 * Foto disimpan di disk "public"; penghapusan berkas lama ditangani trait PunyaFoto di model.
 */
trait KelolaFoto
{
    /** @return array<string, mixed> */
    protected function dataDenganFoto(FormRequest $request, string $folder): array
    {
        $data = $request->safe()->except(['foto', 'hapus_foto']);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store($folder, 'public');
        } elseif ($request->boolean('hapus_foto')) {
            $data['foto'] = null;
        }

        return $data;
    }
}
