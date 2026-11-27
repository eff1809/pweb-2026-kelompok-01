<?php

namespace App\Http\Requests;

use App\Models\Peminjaman;
use Illuminate\Foundation\Http\FormRequest;

class SetujuiPeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $peminjaman = $this->route('peminjaman');

        return $peminjaman instanceof Peminjaman
            && ($this->user()?->can('setujui', $peminjaman) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'catatan_admin' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'catatan_admin.max' => 'Catatan admin maksimal 500 karakter.',
        ];
    }
}
