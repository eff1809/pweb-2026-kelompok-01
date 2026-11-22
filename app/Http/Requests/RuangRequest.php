<?php

namespace App\Http\Requests;

use App\Models\Ruang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form tambah dan ubah Ruang (Admin).
 * Akses Admin sudah dijaga middleware role:admin pada route.
 */
class RuangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Saat ubah, route('ruang') berisi model yang sedang diubah sehingga namanya sendiri diabaikan.
            'nama' => ['required', 'string', 'max:100', Rule::unique('ruang', 'nama')->ignore($this->route('ruang'))],
            'lokasi' => ['required', 'string', 'max:150'],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:500'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(array_keys(Ruang::DAFTAR_STATUS))],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'string' => ':Attribute harus berupa teks.',
            'integer' => ':Attribute harus berupa bilangan bulat.',
            'max.string' => ':Attribute maksimal :max karakter.',
            'nama.unique' => 'Nama ruang sudah digunakan.',
            'kapasitas.min' => 'Kapasitas minimal :min orang.',
            'kapasitas.max' => 'Kapasitas maksimal :max orang.',
            'status.in' => 'Status ruang tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nama' => 'nama ruang',
            'lokasi' => 'lokasi',
            'kapasitas' => 'kapasitas',
            'deskripsi' => 'deskripsi',
            'status' => 'status',
        ];
    }
}
