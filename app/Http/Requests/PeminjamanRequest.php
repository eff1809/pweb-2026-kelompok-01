<?php

namespace App\Http\Requests;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\Ruang;
use App\Services\KetersediaanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class PeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $peminjaman = $this->route('peminjaman');

        if ($peminjaman instanceof Peminjaman) {
            return $user->can('update', $peminjaman);
        }

        return $user->can('create', Peminjaman::class);
    }

    protected function prepareForValidation(): void
    {
        $ruangId = $this->input('ruang_id');

        if ($ruangId === '') {
            $ruangId = null;
        }

        $alat = $this->input('alat');

        if (is_array($alat)) {
            $alat = array_values(array_filter(
                $alat,
                fn ($item) => is_array($item) && (
                    ($item['alat_id'] ?? null) !== null && ($item['alat_id'] ?? '') !== ''
                    || ($item['jumlah'] ?? null) !== null && ($item['jumlah'] ?? '') !== ''
                ),
            ));

            if ($alat === []) {
                $alat = null;
            }
        }

        $this->merge([
            'ruang_id' => $ruangId,
            'alat' => $alat,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $batasMaksimal = now()->addDays(30)->toDateString();

        return [
            'ruang_id' => ['nullable', 'required_without:alat', 'integer', 'exists:ruang,id'],
            'tanggal' => ['required', 'date', 'after_or_equal:today', "before_or_equal:{$batasMaksimal}"],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
            'keperluan' => ['required', 'string', 'min:10', 'max:500'],
            'alat' => ['nullable', 'array', 'required_without:ruang_id'],
            'alat.*.alat_id' => ['required', 'distinct', 'integer', 'exists:alat,id'],
            'alat.*.jumlah' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ruang_id.required_without' => 'Pilih ruang atau minimal satu alat.',
            'ruang_id.exists' => 'Ruang yang dipilih tidak ditemukan.',
            'tanggal.required' => 'Tanggal peminjaman wajib diisi.',
            'tanggal.date' => 'Format tanggal peminjaman tidak valid.',
            'tanggal.after_or_equal' => 'Tanggal tidak boleh sebelum hari ini.',
            'tanggal.before_or_equal' => 'Pengajuan paling lambat 30 hari ke depan.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format' => 'Format jam mulai harus JJ:MM.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.date_format' => 'Format jam selesai harus JJ:MM.',
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai.',
            'keperluan.required' => 'Keperluan wajib diisi.',
            'keperluan.min' => 'Keperluan minimal 10 karakter.',
            'keperluan.max' => 'Keperluan maksimal 500 karakter.',
            'alat.required_without' => 'Pilih ruang atau minimal satu alat.',
            'alat.array' => 'Format daftar alat tidak valid.',
            'alat.*.alat_id.required' => 'Alat wajib dipilih.',
            'alat.*.alat_id.distinct' => 'Alat yang sama dipilih lebih dari sekali.',
            'alat.*.alat_id.exists' => 'Alat yang dipilih tidak ditemukan.',
            'alat.*.jumlah.required' => 'Jumlah alat wajib diisi.',
            'alat.*.jumlah.integer' => 'Jumlah alat harus berupa angka bulat.',
            'alat.*.jumlah.min' => 'Jumlah alat minimal 1.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(KetersediaanService $ketersediaan): array
    {
        return [
            function (Validator $validator) use ($ketersediaan): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $tanggal = (string) $this->input('tanggal');
                $jamMulai = (string) $this->input('jam_mulai');
                $jamSelesai = (string) $this->input('jam_selesai');

                $mulai = Carbon::createFromFormat('Y-m-d H:i', "{$tanggal} {$jamMulai}");
                $selesai = Carbon::createFromFormat('Y-m-d H:i', "{$tanggal} {$jamSelesai}");

                // Jam operasional laboratorium: 07.00–18.00 (aturan P4).
                if ($jamMulai < '07:00' || $jamMulai > '18:00') {
                    $validator->errors()->add('jam_mulai', 'Peminjaman hanya pada jam operasional 07.00–18.00.');
                }

                if ($jamSelesai < '07:00' || $jamSelesai > '18:00') {
                    $validator->errors()->add('jam_selesai', 'Peminjaman hanya pada jam operasional 07.00–18.00.');
                }

                // Durasi maksimal 8 jam per pengajuan.
                if ($mulai->diffInMinutes($selesai) > 480) {
                    $validator->errors()->add('jam_selesai', 'Durasi peminjaman maksimal 8 jam.');
                }

                // Jika tanggal = hari ini, jam mulai harus setelah jam sekarang (aturan P2).
                if ($mulai->isToday() && $mulai->lte(now())) {
                    $validator->errors()->add('jam_mulai', 'Jam mulai sudah lewat.');
                }

                $peminjaman = $this->route('peminjaman');
                $kecualiId = $peminjaman instanceof Peminjaman ? $peminjaman->id : null;

                // Cek status dan bentrok jadwal ruang (aturan P6 & bentrok).
                $ruangId = $this->input('ruang_id');
                if ($ruangId !== null) {
                    $ruang = Ruang::find((int) $ruangId);

                    if ($ruang !== null && ! $ruang->isTersedia()) {
                        $validator->errors()->add('ruang_id', 'Ruang sedang tidak dapat dipinjam.');
                    } elseif ($ruang !== null && $ketersediaan->ruangBentrok(
                        $ruang->id,
                        $tanggal,
                        $jamMulai,
                        $jamSelesai,
                        $kecualiId,
                    )) {
                        $validator->errors()->add(
                            'ruang_id',
                            "Ruang sudah dipakai pada {$tanggal} pukul {$jamMulai}–{$jamSelesai}.",
                        );
                    }
                }

                // Cek kondisi dan ketersediaan stok tiap alat (aturan P6 & stok).
                $daftarAlat = $this->input('alat');
                if (is_array($daftarAlat)) {
                    foreach ($daftarAlat as $index => $item) {
                        $alat = Alat::find((int) $item['alat_id']);

                        if ($alat === null) {
                            continue;
                        }

                        if (! $alat->bisaDipinjam()) {
                            $validator->errors()->add(
                                "alat.{$index}.alat_id",
                                "{$alat->nama} sedang rusak dan tidak dapat dipinjam.",
                            );

                            continue;
                        }

                        $tersedia = $ketersediaan->stokTersedia(
                            $alat,
                            $tanggal,
                            $jamMulai,
                            $jamSelesai,
                            $kecualiId,
                        );

                        if ((int) $item['jumlah'] > $tersedia) {
                            $validator->errors()->add(
                                "alat.{$index}.jumlah",
                                "Stok {$alat->nama} hanya tersedia {$tersedia} unit pada jam tersebut.",
                            );
                        }
                    }
                }
            },
        ];
    }
}
