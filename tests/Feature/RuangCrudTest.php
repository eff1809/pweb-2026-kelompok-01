<?php

namespace Tests\Feature;

use App\Models\Peminjaman;
use App\Models\Ruang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class RuangCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // View sementara dipakai selama view asli belum dibuat tim frontend.
        View::addLocation(base_path('tests/Fixtures/views'));
        $this->withoutVite();

        $this->admin = User::factory()->admin()->create();
    }

    private function dataValid(array $ubah = []): array
    {
        return array_merge([
            'nama' => 'Laboratorium Jaringan Komputer',
            'lokasi' => 'Gedung Teknik Informatika, Lantai 2',
            'kapasitas' => 40,
            'deskripsi' => 'Dilengkapi 40 PC dan perangkat jaringan Cisco.',
            'status' => Ruang::STATUS_TERSEDIA,
        ], $ubah);
    }

    public function test_admin_dapat_melihat_daftar_ruang_dengan_pagination(): void
    {
        Ruang::factory()->count(12)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.ruang.index'));

        $response->assertOk()->assertViewHas('ruang', fn ($ruang) => $ruang->count() === 10 && $ruang->total() === 12);
    }

    public function test_admin_dapat_membuka_form_tambah_detail_dan_ubah(): void
    {
        $ruang = Ruang::factory()->create();

        $this->actingAs($this->admin)->get(route('admin.ruang.create'))->assertOk()->assertViewHas('daftarStatus');
        $this->actingAs($this->admin)->get(route('admin.ruang.show', $ruang))->assertOk()->assertViewHas('ruang');
        $this->actingAs($this->admin)->get(route('admin.ruang.edit', $ruang))->assertOk()->assertViewHas('ruang');
    }

    public function test_admin_dapat_menambah_ruang(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.ruang.store'), $this->dataValid());

        $response->assertRedirect(route('admin.ruang.index'))->assertSessionHas('success');
        $this->assertDatabaseHas('ruang', ['nama' => 'Laboratorium Jaringan Komputer', 'kapasitas' => 40]);
    }

    public function test_tambah_ruang_menampilkan_pesan_error_berbahasa_indonesia(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.ruang.store'), [
            'nama' => '',
            'lokasi' => '',
            'kapasitas' => 0,
            'status' => 'rusak',
        ]);

        $response->assertSessionHasErrors([
            'nama' => 'Nama ruang wajib diisi.',
            'lokasi' => 'Lokasi wajib diisi.',
            'kapasitas' => 'Kapasitas minimal 1 orang.',
            'status' => 'Status ruang tidak valid.',
        ]);
        $this->assertDatabaseCount('ruang', 0);
    }

    public function test_nama_ruang_harus_unik(): void
    {
        Ruang::factory()->create(['nama' => 'Laboratorium Jaringan Komputer']);

        $response = $this->actingAs($this->admin)->post(route('admin.ruang.store'), $this->dataValid());

        $response->assertSessionHasErrors(['nama' => 'Nama ruang sudah digunakan.']);
    }

    public function test_admin_dapat_mengubah_ruang_tanpa_bentrok_dengan_namanya_sendiri(): void
    {
        $ruang = Ruang::factory()->create(['nama' => 'Laboratorium Jaringan Komputer']);

        $response = $this->actingAs($this->admin)->put(
            route('admin.ruang.update', $ruang),
            $this->dataValid(['kapasitas' => 35, 'status' => Ruang::STATUS_PERBAIKAN])
        );

        $response->assertRedirect(route('admin.ruang.index'))->assertSessionHasNoErrors();
        $this->assertSame(35, $ruang->fresh()->kapasitas);
        $this->assertSame(Ruang::STATUS_PERBAIKAN, $ruang->fresh()->status);
    }

    public function test_ubah_ruang_ditolak_jika_memakai_nama_ruang_lain(): void
    {
        Ruang::factory()->create(['nama' => 'Laboratorium Multimedia']);
        $ruang = Ruang::factory()->create();

        $response = $this->actingAs($this->admin)->put(
            route('admin.ruang.update', $ruang),
            $this->dataValid(['nama' => 'Laboratorium Multimedia'])
        );

        $response->assertSessionHasErrors(['nama' => 'Nama ruang sudah digunakan.']);
    }

    public function test_admin_dapat_menghapus_ruang(): void
    {
        $ruang = Ruang::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.ruang.destroy', $ruang));

        $response->assertRedirect(route('admin.ruang.index'))->assertSessionHas('success');
        $this->assertModelMissing($ruang);
    }

    public function test_ruang_dengan_riwayat_peminjaman_tidak_dapat_dihapus(): void
    {
        $ruang = Ruang::factory()->create();
        Peminjaman::factory()->dikembalikan()->create(['ruang_id' => $ruang->id]);

        $response = $this->actingAs($this->admin)->delete(route('admin.ruang.destroy', $ruang));

        $response->assertRedirect(route('admin.ruang.index'))
            ->assertSessionHas('error')
            ->assertSessionMissing('success');
        $this->assertModelExists($ruang);
    }

    public function test_mahasiswa_tidak_dapat_mengakses_crud_ruang(): void
    {
        $mahasiswa = User::factory()->create();
        $ruang = Ruang::factory()->create();

        $this->actingAs($mahasiswa)->get(route('admin.ruang.index'))->assertForbidden();
        $this->actingAs($mahasiswa)->post(route('admin.ruang.store'), $this->dataValid())->assertForbidden();
        $this->actingAs($mahasiswa)->put(route('admin.ruang.update', $ruang), $this->dataValid())->assertForbidden();
        $this->actingAs($mahasiswa)->delete(route('admin.ruang.destroy', $ruang))->assertForbidden();

        $this->assertModelExists($ruang);
        $this->assertDatabaseMissing('ruang', ['nama' => 'Laboratorium Jaringan Komputer']);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get(route('admin.ruang.index'))->assertRedirect(route('login'));
        $this->get(route('ruang.index'))->assertRedirect(route('login'));
    }

    public function test_katalog_ruang_tidak_menampilkan_ruang_nonaktif(): void
    {
        $mahasiswa = User::factory()->create();
        $tersedia = Ruang::factory()->create();
        $perbaikan = Ruang::factory()->status(Ruang::STATUS_PERBAIKAN)->create();
        $nonaktif = Ruang::factory()->status(Ruang::STATUS_NONAKTIF)->create();

        $response = $this->actingAs($mahasiswa)->get(route('ruang.index'));

        $response->assertOk()->assertViewHas('ruang', function ($ruang) use ($tersedia, $perbaikan, $nonaktif) {
            $id = $ruang->pluck('id');

            return $id->contains($tersedia->id) && $id->contains($perbaikan->id) && ! $id->contains($nonaktif->id);
        });

        $this->actingAs($mahasiswa)->get(route('ruang.show', $tersedia))->assertOk();
        $this->actingAs($mahasiswa)->get(route('ruang.show', $nonaktif))->assertNotFound();
    }
}
