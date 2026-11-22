<?php

namespace App\Http\Requests;

use App\Models\Alat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form tambah dan ubah Alat (Admin).
 * Akses Admin sudah dijaga middleware role:admin pada route.
 */
class AlatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Kode alat disimpan dalam huruf kapital agar "prj-001" dan "PRJ-001" dianggap sama. */
    protected function prepareForValidation(): void
    {
        if (is_string($this->kode)) {
            $this->merge(['kode' => strtoupper(trim($this->kode))]);
        }
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            // Saat ubah, route('alat') berisi model yang sedang diubah sehingga kodenya sendiri diabaikan.
            'kode' => ['required', 'string', 'max:20', 'alpha_dash:ascii', Rule::unique('alat', 'kode')->ignore($this->route('alat'))],
            'kategori' => ['required', 'string', 'max:50'],
            'jumlah_total' => ['required', 'integer', 'min:1', 'max:1000'],
            'kondisi' => ['required', Rule::in(array_keys(Alat::DAFTAR_KONDISI))],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':Attribute wajib diisi.',
            'string' => ':Attribute harus berupa teks.',
            'integer' => ':Attribute harus berupa bilangan bulat.',
            'max.string' => ':Attribute maksimal :max karakter.',
            'kode.alpha_dash' => 'Kode hanya boleh berisi huruf, angka, dan tanda hubung.',
            'kode.unique' => 'Kode alat sudah digunakan.',
            'jumlah_total.min' => 'Jumlah alat minimal :min unit.',
            'jumlah_total.max' => 'Jumlah alat maksimal :max unit.',
            'kondisi.in' => 'Kondisi alat tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'nama' => 'nama alat',
            'kode' => 'kode alat',
            'kategori' => 'kategori',
            'jumlah_total' => 'jumlah alat',
            'kondisi' => 'kondisi',
        ];
    }
}
