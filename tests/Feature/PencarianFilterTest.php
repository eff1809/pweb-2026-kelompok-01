<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Ruang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Pencarian, filter, dan pagination Ruang dan Alat (spesifikasi 2.5).
 */
class PencarianFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $mahasiswa;

    protected function setUp(): void
    {
        parent::setUp();

        View::addLocation(base_path('tests/Fixtures/views'));
        $this->withoutVite();

        $this->admin = User::factory()->admin()->create();
        $this->mahasiswa = User::factory()->create();
    }

    private function namaDi(string $variabel, $response): array
    {
        return $response->viewData($variabel)->pluck('nama')->all();
    }

    private function buatRuangContoh(): void
    {
        Ruang::factory()->create(['nama' => 'Lab Jaringan', 'lokasi' => 'Gedung A Lt. 2', 'status' => Ruang::STATUS_TERSEDIA]);
        Ruang::factory()->create(['nama' => 'Lab Multimedia', 'lokasi' => 'Gedung B Lt. 1', 'status' => Ruang::STATUS_PERBAIKAN]);
        Ruang::factory()->create(['nama' => 'Ruang Seminar', 'lokasi' => 'Gedung A Lt. 3', 'status' => Ruang::STATUS_NONAKTIF]);
    }

    private function buatAlatContoh(): void
    {
        Alat::factory()->create(['nama' => 'Proyektor Epson', 'kode' => 'PRJ-001', 'kategori' => 'Presentasi', 'kondisi' => Alat::KONDISI_BAIK]);
        Alat::factory()->create(['nama' => 'Router Mikrotik', 'kode' => 'NET-001', 'kategori' => 'Jaringan', 'kondisi' => Alat::KONDISI_RUSAK_RINGAN]);
        Alat::factory()->create(['nama' => 'Switch Cisco', 'kode' => 'NET-002', 'kategori' => 'Jaringan', 'kondisi' => Alat::KONDISI_RUSAK_BERAT]);
    }

    public function test_admin_mencari_ruang_berdasarkan_nama_atau_lokasi(): void
    {
        $this->buatRuangContoh();

        $berdasarNama = $this->actingAs($this->admin)->get(route('admin.ruang.index', ['q' => 'multi']));
        $berdasarLokasi = $this->actingAs($this->admin)->get(route('admin.ruang.index', ['q' => 'Gedung A']));

        $this->assertSame(['Lab Multimedia'], $this->namaDi('ruang', $berdasarNama));
        $this->assertSame(['Lab Jaringan', 'Ruang Seminar'], $this->namaDi('ruang', $berdasarLokasi));
        $berdasarNama->assertViewHas('filter', ['q' => 'multi', 'status' => null]);
    }

    public function test_admin_memfilter_ruang_berdasarkan_status_dan_menggabungkan_dengan_pencarian(): void
    {
        $this->buatRuangContoh();

        $response = $this->actingAs($this->admin)->get(route('admin.ruang.index', ['status' => 'nonaktif']));
        $gabungan = $this->actingAs($this->admin)->get(route('admin.ruang.index', ['q' => 'Lab', 'status' => 'perbaikan']));

        $this->assertSame(['Ruang Seminar'], $this->namaDi('ruang', $response));
        $this->assertSame(['Lab Multimedia'], $this->namaDi('ruang', $gabungan));
    }

    public function test_parameter_filter_tidak_valid_diabaikan_tanpa_error(): void
    {
        $this->buatRuangContoh();
        $this->buatAlatContoh();

        $ruang = $this->actingAs($this->admin)->get('/admin/ruang?status=hilang&q[]=x');
        $alat = $this->actingAs($this->admin)->get('/admin/alat?kondisi=hilang&kategori=Tidak%20Ada&q=%20%20');

        $ruang->assertOk()->assertViewHas('filter', ['q' => null, 'status' => null]);
        $this->assertCount(3, $ruang->viewData('ruang'));
        $alat->assertOk()->assertViewHas('filter', ['q' => null, 'kategori' => null, 'kondisi' => null]);
        $this->assertCount(3, $alat->viewData('alat'));
    }

    public function test_pagination_ruang_membawa_query_string_filter(): void
    {
        Ruang::factory()->count(12)->sequence(fn ($s) => ['nama' => 'Lab '.str_pad($s->index, 2, '0', STR_PAD_LEFT)])->create();
        Ruang::factory()->count(3)->sequence(fn ($s) => ['nama' => 'Gudang '.$s->index])->create();

        $response = $this->actingAs($this->admin)->get(route('admin.ruang.index', ['q' => 'Lab', 'page' => 2]));

        $paginator = $response->viewData('ruang');
        $this->assertSame(12, $paginator->total());
        $this->assertCount(2, $paginator->items());
        $this->assertStringContainsString('q=Lab', $paginator->url(1));
    }

    public function test_katalog_ruang_dapat_dicari_tetapi_ruang_nonaktif_tetap_tersembunyi(): void
    {
        $this->buatRuangContoh();

        $semua = $this->actingAs($this->mahasiswa)->get(route('ruang.index', ['q' => 'Gedung A']));
        $filterNonaktif = $this->actingAs($this->mahasiswa)->get(route('ruang.index', ['status' => 'nonaktif']));
        $filterPerbaikan = $this->actingAs($this->mahasiswa)->get(route('ruang.index', ['status' => 'perbaikan']));

        $this->assertSame(['Lab Jaringan'], $this->namaDi('ruang', $semua));
        $this->assertSame(['Lab Jaringan', 'Lab Multimedia'], $this->namaDi('ruang', $filterNonaktif));
        $this->assertSame(['Lab Multimedia'], $this->namaDi('ruang', $filterPerbaikan));
        $filterNonaktif->assertViewHas('daftarStatus', ['tersedia' => 'Tersedia', 'perbaikan' => 'Perbaikan']);
    }

    public function test_admin_mencari_alat_berdasarkan_nama_atau_kode(): void
    {
        $this->buatAlatContoh();

        $berdasarNama = $this->actingAs($this->admin)->get(route('admin.alat.index', ['q' => 'proyektor']));
        $berdasarKode = $this->actingAs($this->admin)->get(route('admin.alat.index', ['q' => 'NET-']));

        $this->assertSame(['Proyektor Epson'], $this->namaDi('alat', $berdasarNama));
        $this->assertSame(['Router Mikrotik', 'Switch Cisco'], $this->namaDi('alat', $berdasarKode));
    }

    public function test_admin_memfilter_alat_berdasarkan_kategori_dan_kondisi(): void
    {
        $this->buatAlatContoh();

        $kategori = $this->actingAs($this->admin)->get(route('admin.alat.index', ['kategori' => 'Jaringan']));
        $gabungan = $this->actingAs($this->admin)->get(route('admin.alat.index', ['kategori' => 'Jaringan', 'kondisi' => 'rusak_berat']));

        $this->assertSame(['Router Mikrotik', 'Switch Cisco'], $this->namaDi('alat', $kategori));
        $this->assertSame(['Switch Cisco'], $this->namaDi('alat', $gabungan));
        $kategori->assertViewHas('daftarKategori', ['Jaringan', 'Presentasi']);
    }

    public function test_katalog_alat_dapat_dicari_dan_difilter_oleh_mahasiswa(): void
    {
        $this->buatAlatContoh();

        $response = $this->actingAs($this->mahasiswa)->get(route('alat.index', ['q' => 'cisco', 'kondisi' => 'rusak_berat']));

        $response->assertOk()
            ->assertViewHas('filter', ['q' => 'cisco', 'kategori' => null, 'kondisi' => 'rusak_berat'])
            ->assertViewHas('daftarKondisi', Alat::DAFTAR_KONDISI);
        $this->assertSame(['Switch Cisco'], $this->namaDi('alat', $response));
    }
}
