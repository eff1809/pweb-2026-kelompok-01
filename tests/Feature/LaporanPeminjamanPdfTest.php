<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\Ruang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanPeminjamanPdfTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->mahasiswa = User::factory()->create();
    }

    public function test_admin_dapat_mengunduh_pdf_rekap_peminjaman(): void
    {
        $ruang = Ruang::factory()->create();
        $alat = Alat::factory()->create();

        $peminjaman = Peminjaman::factory()->disetujui($this->admin)->create([
            'user_id' => $this->mahasiswa->id,
            'ruang_id' => $ruang->id,
        ]);
        $peminjaman->alat()->attach($alat->id, ['jumlah' => 2]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.laporan.peminjaman.pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_admin_dapat_membuka_pratinjau_pdf_dengan_filter(): void
    {
        Peminjaman::factory()->disetujui($this->admin)->create([
            'tanggal' => '2026-12-01',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.laporan.peminjaman.pdf', [
            'status' => Peminjaman::STATUS_DISETUJUI,
            'dari' => '2026-12-01',
            'sampai' => '2026-12-31',
            'preview' => 1,
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('inline;', (string) $response->headers->get('content-disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_parameter_filter_tidak_valid_diabaikan_tanpa_error(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.laporan.peminjaman.pdf', [
            'status' => 'status_ngawur',
            'dari' => 'bukan-tanggal',
            'sampai' => '2026-99',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_mahasiswa_dan_tamu_tidak_dapat_mengakses_ekspor_pdf(): void
    {
        $this->get(route('admin.laporan.peminjaman.pdf'))->assertRedirect(route('login'));
        $this->actingAs($this->mahasiswa)->get(route('admin.laporan.peminjaman.pdf'))->assertForbidden();
    }
}
