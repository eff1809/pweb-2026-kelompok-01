<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\Ruang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Upload foto Ruang dan Alat (nilai tambah): validasi tipe/ukuran, simpan, ganti, hapus.
 */
class UploadFotoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        View::addLocation(base_path('tests/Fixtures/views'));
        $this->withoutVite();
        Storage::fake('public');

        $this->admin = User::factory()->admin()->create();
    }

    private function dataRuang(array $ubah = []): array
    {
        return array_merge([
            'nama' => 'Lab Jaringan Komputer',
            'lokasi' => 'Gedung A Lt. 2',
            'kapasitas' => 40,
            'deskripsi' => null,
            'status' => Ruang::STATUS_TERSEDIA,
        ], $ubah);
    }

    private function dataAlat(array $ubah = []): array
    {
        return array_merge([
            'nama' => 'Proyektor Epson EB-X51',
            'kode' => 'PRJ-001',
            'kategori' => 'Presentasi',
            'jumlah_total' => 5,
            'kondisi' => Alat::KONDISI_BAIK,
        ], $ubah);
    }

    public function test_admin_dapat_menambah_ruang_dengan_foto(): void
    {
        $this->actingAs($this->admin)->post(route('admin.ruang.store'), $this->dataRuang([
            'foto' => UploadedFile::fake()->image('lab.jpg', 800, 600),
        ]))->assertRedirect(route('admin.ruang.index'));

        $ruang = Ruang::firstOrFail();
        $this->assertStringStartsWith('ruang/', $ruang->foto);
        Storage::disk('public')->assertExists($ruang->foto);
        $this->assertSame(asset('storage/'.$ruang->foto), $ruang->fotoUrl());
    }

    public function test_foto_bersifat_opsional_dan_memakai_placeholder(): void
    {
        $this->actingAs($this->admin)->post(route('admin.alat.store'), $this->dataAlat())
            ->assertSessionHasNoErrors();

        $alat = Alat::firstOrFail();
        $this->assertNull($alat->foto);
        $this->assertSame(asset('images/placeholder-foto.svg'), $alat->fotoUrl());
        $this->assertFileExists(public_path('images/placeholder-foto.svg'));
    }

    public function test_foto_harus_gambar_jpg_png_atau_webp(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.ruang.store'), $this->dataRuang([
            'foto' => UploadedFile::fake()->create('jadwal.pdf', 100, 'application/pdf'),
        ]));

        $response->assertSessionHasErrors(['foto' => 'Foto harus berupa gambar JPG, PNG, atau WEBP.']);
        $this->assertDatabaseCount('ruang', 0);
    }

    public function test_gambar_gif_ditolak(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.alat.store'), $this->dataAlat([
            'foto' => UploadedFile::fake()->image('animasi.gif'),
        ]));

        $response->assertSessionHasErrors(['foto' => 'Foto harus berupa gambar JPG, PNG, atau WEBP.']);
    }

    public function test_ukuran_foto_maksimal_2_mb(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.alat.store'), $this->dataAlat([
            'foto' => UploadedFile::fake()->image('besar.png')->size(2049),
        ]));

        $response->assertSessionHasErrors(['foto' => 'Ukuran foto maksimal 2 MB.']);
        $this->assertDatabaseCount('alat', 0);
    }

    public function test_mengganti_foto_menghapus_berkas_lama(): void
    {
        $lama = UploadedFile::fake()->image('lama.jpg')->store('alat', 'public');
        $alat = Alat::factory()->create(['kode' => 'PRJ-001', 'foto' => $lama]);

        $this->actingAs($this->admin)->put(route('admin.alat.update', $alat), $this->dataAlat([
            'foto' => UploadedFile::fake()->image('baru.webp'),
        ]))->assertSessionHasNoErrors();

        $alat->refresh();
        $this->assertNotSame($lama, $alat->foto);
        Storage::disk('public')->assertMissing($lama);
        Storage::disk('public')->assertExists($alat->foto);
    }

    public function test_ubah_tanpa_foto_baru_mempertahankan_foto_lama(): void
    {
        $lama = UploadedFile::fake()->image('lama.jpg')->store('ruang', 'public');
        $ruang = Ruang::factory()->create(['foto' => $lama]);

        $this->actingAs($this->admin)->put(route('admin.ruang.update', $ruang), $this->dataRuang(['kapasitas' => 30]))
            ->assertSessionHasNoErrors();

        $this->assertSame($lama, $ruang->refresh()->foto);
        Storage::disk('public')->assertExists($lama);
    }

    public function test_centang_hapus_foto_mengosongkan_foto(): void
    {
        $lama = UploadedFile::fake()->image('lama.jpg')->store('ruang', 'public');
        $ruang = Ruang::factory()->create(['foto' => $lama]);

        $this->actingAs($this->admin)->put(route('admin.ruang.update', $ruang), $this->dataRuang(['hapus_foto' => '1']))
            ->assertSessionHasNoErrors();

        $this->assertNull($ruang->refresh()->foto);
        Storage::disk('public')->assertMissing($lama);
    }

    public function test_menghapus_data_ikut_menghapus_berkas_foto(): void
    {
        $fotoRuang = UploadedFile::fake()->image('r.jpg')->store('ruang', 'public');
        $fotoAlat = UploadedFile::fake()->image('a.jpg')->store('alat', 'public');
        $ruang = Ruang::factory()->create(['foto' => $fotoRuang]);
        $alat = Alat::factory()->create(['foto' => $fotoAlat]);

        $this->actingAs($this->admin)->delete(route('admin.ruang.destroy', $ruang));
        $this->actingAs($this->admin)->delete(route('admin.alat.destroy', $alat));

        Storage::disk('public')->assertMissing($fotoRuang);
        Storage::disk('public')->assertMissing($fotoAlat);
    }

    public function test_foto_tetap_ada_jika_hapus_ditolak_karena_ada_riwayat(): void
    {
        $foto = UploadedFile::fake()->image('r.jpg')->store('ruang', 'public');
        $ruang = Ruang::factory()->create(['foto' => $foto]);
        Peminjaman::factory()->create(['ruang_id' => $ruang->id]);

        $this->actingAs($this->admin)->delete(route('admin.ruang.destroy', $ruang))->assertSessionHas('error');

        Storage::disk('public')->assertExists($foto);
    }
}
