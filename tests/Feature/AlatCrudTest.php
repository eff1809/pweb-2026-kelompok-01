<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class AlatCrudTest extends TestCase
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
            'nama' => 'Proyektor Epson EB-X51',
            'kode' => 'PRJ-001',
            'kategori' => 'Presentasi',
            'jumlah_total' => 5,
            'kondisi' => Alat::KONDISI_BAIK,
        ], $ubah);
    }

    public function test_admin_dapat_melihat_daftar_alat_dengan_pagination(): void
    {
        Alat::factory()->count(15)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.alat.index'));

        $response->assertOk()->assertViewHas('alat', fn ($alat) => $alat->count() === 10 && $alat->total() === 15);
    }

    public function test_form_alat_menyediakan_daftar_kategori_yang_sudah_ada(): void
    {
        Alat::factory()->create(['kategori' => 'Jaringan']);
        Alat::factory()->create(['kategori' => 'Jaringan']);
        Alat::factory()->create(['kategori' => 'Alat Ukur']);

        $response = $this->actingAs($this->admin)->get(route('admin.alat.create'));

        $response->assertOk()->assertViewHas('daftarKategori', ['Alat Ukur', 'Jaringan']);
    }

    public function test_admin_dapat_menambah_alat_dan_kode_disimpan_kapital(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.alat.store'), $this->dataValid(['kode' => ' prj-001 ']));

        $response->assertRedirect(route('admin.alat.index'))->assertSessionHas('success');
        $this->assertDatabaseHas('alat', ['kode' => 'PRJ-001', 'jumlah_total' => 5]);
    }

    public function test_tambah_alat_menampilkan_pesan_error_berbahasa_indonesia(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.alat.store'), [
            'nama' => '',
            'kode' => 'PRJ 001!',
            'kategori' => '',
            'jumlah_total' => 0,
            'kondisi' => 'hilang',
        ]);

        $response->assertSessionHasErrors([
            'nama' => 'Nama alat wajib diisi.',
            'kode' => 'Kode hanya boleh berisi huruf, angka, dan tanda hubung.',
            'kategori' => 'Kategori wajib diisi.',
            'jumlah_total' => 'Jumlah alat minimal 1 unit.',
            'kondisi' => 'Kondisi alat tidak valid.',
        ]);
        $this->assertDatabaseCount('alat', 0);
    }

    public function test_kode_alat_harus_unik_tanpa_memandang_huruf_besar_kecil(): void
    {
        Alat::factory()->create(['kode' => 'PRJ-001']);

        $response = $this->actingAs($this->admin)->post(route('admin.alat.store'), $this->dataValid(['kode' => 'prj-001']));

        $response->assertSessionHasErrors(['kode' => 'Kode alat sudah digunakan.']);
    }

    public function test_admin_dapat_mengubah_alat_dengan_kode_yang_sama(): void
    {
        $alat = Alat::factory()->create(['kode' => 'PRJ-001']);

        $response = $this->actingAs($this->admin)->put(
            route('admin.alat.update', $alat),
            $this->dataValid(['jumlah_total' => 3, 'kondisi' => Alat::KONDISI_RUSAK_RINGAN])
        );

        $response->assertRedirect(route('admin.alat.index'))->assertSessionHasNoErrors();
        $this->assertSame(3, $alat->fresh()->jumlah_total);
        $this->assertSame(Alat::KONDISI_RUSAK_RINGAN, $alat->fresh()->kondisi);
    }

    public function test_admin_dapat_menghapus_alat(): void
    {
        $alat = Alat::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.alat.destroy', $alat));

        $response->assertRedirect(route('admin.alat.index'))->assertSessionHas('success');
        $this->assertModelMissing($alat);
    }

    /** Pasang alat ke sebuah peminjaman dengan status dan jumlah tertentu. */
    private function pinjamkan(Alat $alat, int $jumlah, string $state): Peminjaman
    {
        $peminjaman = Peminjaman::factory()->{$state}()->create(['ruang_id' => null]);
        $peminjaman->alat()->attach($alat->id, ['jumlah' => $jumlah]);

        return $peminjaman;
    }

    public function test_alat_dengan_riwayat_peminjaman_tidak_dapat_dihapus(): void
    {
        $alat = Alat::factory()->create();
        $this->pinjamkan($alat, 1, 'dikembalikan');

        $response = $this->actingAs($this->admin)->delete(route('admin.alat.destroy', $alat));

        $response->assertRedirect(route('admin.alat.index'))
            ->assertSessionHas('error')
            ->assertSessionMissing('success');
        $this->assertModelExists($alat);
    }

    public function test_jumlah_alat_tidak_boleh_kurang_dari_unit_yang_sedang_dipinjam(): void
    {
        $alat = Alat::factory()->create(['kode' => 'PRJ-001', 'jumlah_total' => 5]);
        $this->pinjamkan($alat, 3, 'dipinjam');

        $response = $this->actingAs($this->admin)
            ->from(route('admin.alat.edit', $alat))
            ->put(route('admin.alat.update', $alat), $this->dataValid(['jumlah_total' => 2]));

        $response->assertRedirect(route('admin.alat.edit', $alat))
            ->assertSessionHasErrors([
                'jumlah_total' => 'Jumlah tidak boleh kurang dari unit yang sedang dipinjam (3 unit).',
            ]);
        $this->assertSame(5, $alat->fresh()->jumlah_total);
    }

    public function test_jumlah_alat_boleh_sama_dengan_unit_yang_sedang_dipinjam(): void
    {
        $alat = Alat::factory()->create(['kode' => 'PRJ-001', 'jumlah_total' => 5]);
        $this->pinjamkan($alat, 3, 'dipinjam');

        $response = $this->actingAs($this->admin)
            ->put(route('admin.alat.update', $alat), $this->dataValid(['jumlah_total' => 3]));

        $response->assertRedirect(route('admin.alat.index'))->assertSessionHasNoErrors();
        $this->assertSame(3, $alat->fresh()->jumlah_total);
    }

    public function test_unit_berstatus_selain_dipinjam_tidak_menghalangi_pengurangan_jumlah(): void
    {
        $alat = Alat::factory()->create(['kode' => 'PRJ-001', 'jumlah_total' => 5]);
        $this->pinjamkan($alat, 4, 'dikembalikan');
        $this->pinjamkan($alat, 4, 'disetujui');

        $response = $this->actingAs($this->admin)
            ->put(route('admin.alat.update', $alat), $this->dataValid(['jumlah_total' => 1]));

        $response->assertRedirect(route('admin.alat.index'))->assertSessionHasNoErrors();
        $this->assertSame(1, $alat->fresh()->jumlah_total);
    }

    public function test_mahasiswa_tidak_dapat_mengakses_crud_alat(): void
    {
        $mahasiswa = User::factory()->create();
        $alat = Alat::factory()->create();

        $this->actingAs($mahasiswa)->get(route('admin.alat.index'))->assertForbidden();
        $this->actingAs($mahasiswa)->post(route('admin.alat.store'), $this->dataValid())->assertForbidden();
        $this->actingAs($mahasiswa)->put(route('admin.alat.update', $alat), $this->dataValid())->assertForbidden();
        $this->actingAs($mahasiswa)->delete(route('admin.alat.destroy', $alat))->assertForbidden();

        $this->assertModelExists($alat);
    }

    public function test_mahasiswa_dapat_melihat_katalog_alat(): void
    {
        $mahasiswa = User::factory()->create();
        $alat = Alat::factory()->kondisi(Alat::KONDISI_RUSAK_BERAT)->create();

        $this->actingAs($mahasiswa)->get(route('alat.index'))
            ->assertOk()
            ->assertViewHas('alat', fn ($daftar) => $daftar->contains($alat));
        $this->actingAs($mahasiswa)->get(route('alat.show', $alat))->assertOk();
        $this->assertFalse($alat->bisaDipinjam());
    }
}
