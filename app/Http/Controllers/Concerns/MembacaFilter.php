<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Pembaca parameter pencarian/filter dari query string (spesifikasi 2.5).
 * Parameter yang tidak valid diabaikan (dianggap kosong), tidak memunculkan error.
 */
trait MembacaFilter
{
    /** Teks pencarian yang sudah di-trim; null jika kosong atau bukan teks. */
    protected function teksFilter(Request $request, string $kunci, int $maks = 100): ?string
    {
        $nilai = $request->query($kunci);

        if (! is_string($nilai)) {
            return null;
        }

        $nilai = mb_substr(trim($nilai), 0, $maks);

        return $nilai === '' ? null : $nilai;
    }

    /**
     * Nilai pilihan yang hanya diterima jika ada di daftar yang diizinkan.
     *
     * @param  array<int, string>  $diizinkan
     */
    protected function pilihanFilter(Request $request, string $kunci, array $diizinkan): ?string
    {
        $nilai = $request->query($kunci);

        return is_string($nilai) && in_array($nilai, $diizinkan, true) ? $nilai : null;
    }
}
