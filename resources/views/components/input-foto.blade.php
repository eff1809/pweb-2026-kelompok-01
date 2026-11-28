{{--
    Input upload foto dengan pratinjau (Alpine.js), untuk form Ruang dan Alat.
    Form pemakainya WAJIB memakai enctype="multipart/form-data".

    Contoh:
        <x-input-foto />                                  (form tambah)
        <x-input-foto :model="$ruang" />                  (form ubah: tampilkan foto lama + opsi hapus)
--}}
@props(['model' => null, 'name' => 'foto', 'label' => 'Foto'])

@php
    $fotoLama = $model?->foto ? $model->fotoUrl() : null;
@endphp

<div x-data="{
        pratinjau: @js($fotoLama),
        fotoLama: @js($fotoLama),
        hapus: @js((bool) old('hapus_foto')),
        pilih(event) {
            const berkas = event.target.files[0];
            this.pratinjau = berkas ? URL.createObjectURL(berkas) : this.fotoLama;
            if (berkas) this.hapus = false;
        },
    }" {{ $attributes->merge(['class' => 'space-y-2']) }}>
    <x-input-label :for="$name" :value="$label" />

    <img x-show="pratinjau && !hapus" x-cloak :src="pratinjau" alt="Pratinjau foto"
         class="h-40 w-full max-w-xs rounded-md border border-gray-200 object-cover">

    <input id="{{ $name }}" name="{{ $name }}" type="file" accept="image/jpeg,image/png,image/webp"
           x-ref="berkas" @change="pilih($event)"
           class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200">

    <p class="text-xs text-gray-500">JPG, PNG, atau WEBP, maksimal 2 MB. Kosongkan jika tidak ingin mengubah foto.</p>

    @if ($fotoLama)
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="hapus_foto" value="1" x-model="hapus"
                   @change="if (hapus) { $refs.berkas.value = ''; pratinjau = fotoLama; }"
                   class="rounded border-gray-300">
            Hapus foto saat ini
        </label>
    @endif

    <x-input-error :messages="$errors->get($name)" />
</div>
