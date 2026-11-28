<?php

namespace App\Http\Requests;

use App\Models\Peminjaman;
use Illuminate\Foundation\Http\FormRequest;

class TolakPeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $peminjaman = $this->route('peminjaman');

        return $peminjaman instanceof Peminjaman
            && ($this->user()?->can('tolak', $peminjaman) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'catatan_admin' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'catatan_admin.required' => 'Alasan penolakan wajib diisi.',
            'catatan_admin.min' => 'Alasan penolakan minimal 5 karakter.',
            'catatan_admin.max' => 'Alasan penolakan maksimal 500 karakter.',
        ];
    }
}
