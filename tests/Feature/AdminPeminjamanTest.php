<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\Ruang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class AdminPeminjamanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $mahasiswa;

    private Ruang $ruang;

    private Alat $alat;

    protected function setUp(): void
    {
        parent::setUp();

        View::addLocation(base_path('tests/Fixtures/views'));
        $this->withoutVite();

        $this->admin = User::factory()->admin()->create();
        $this->mahasiswa = User::factory()->create();
        $this->ruang = Ruang::factory()->create(['status' => Ruang::STATUS_TERSEDIA]);
        $this->alat = Alat::factory()->create([
            'jumlah_total' => 3,
            'kondisi' => Alat::KONDISI_BAIK,
        ]);
    }

    public function test_admin_dapat_menyetujui_menyerahkan_dan_mencatat_pengembalian(): void
    {
        $peminjaman = Peminjaman::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'ruang_id' => $this->ruang->id,
            'status' => Peminjaman::STATUS_DIAJUKAN,
        ]);
        $peminjaman->alat()->attach($this->alat->id, ['jumlah' => 2]);

        // Setujui (diajukan -> disetujui)
        $this->actingAs($this->admin)
            ->patch(route('admin.peminjaman.setujui', $peminjaman), [
                'catatan_admin' => 'Silakan ambil kunci di ruang teknisi.',
            ])
            ->assertRedirect(route('admin.peminjaman.show', $peminjaman))
            ->assertSessionHas('success');

        $peminjaman->refresh();
        $this->assertSame(Peminjaman::STATUS_DISETUJUI, $peminjaman->status);
        $this->assertSame($this->admin->id, $peminjaman->diproses_oleh);
        $this->assertNotNull($peminjaman->diproses_pada);

        // Serahkan (disetujui -> dipinjam)
        $this->actingAs($this->admin)
            ->patch(route('admin.peminjaman.serahkan', $peminjaman))
            ->assertRedirect(route('admin.peminjaman.show', $peminjaman))
            ->assertSessionHas('success');

        $peminjaman->refresh();
        $this->assertSame(Peminjaman::STATUS_DIPINJAM, $peminjaman->status);
        $this->assertNotNull($peminjaman->dipinjam_pada);

        // Kembalikan (dipinjam -> dikembalikan)
        $this->actingAs($this->admin)
            ->patch(route('admin.peminjaman.kembalikan', $peminjaman))
            ->assertRedirect(route('admin.peminjaman.show', $peminjaman))
            ->assertSessionHas('success');

        $peminjaman->refresh();
        $this->assertSame(Peminjaman::STATUS_DIKEMBALIKAN, $peminjaman->status);
        $this->assertNotNull($peminjaman->dikembalikan_pada);
    }

    public function test_persetujuan_admin_ditolak_jika_slot_sudah_diambil_peminjaman_lain(): void
    {
        $tanggal = now()->addDays(2)->toDateString();

        $pertama = Peminjaman::factory()->create([
            'ruang_id' => $this->ruang->id,
            'tanggal' => $tanggal,
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '11:00:00',
            'status' => Peminjaman::STATUS_DIAJUKAN,
        ]);

        $kedua = Peminjaman::factory()->create([
            'ruang_id' => $this->ruang->id,
            'tanggal' => $tanggal,
            'jam_mulai' => '10:00:00',
            'jam_selesai' => '12:00:00',
            'status' => Peminjaman::STATUS_DIAJUKAN,
        ]);

        // Admin menyetujui yang pertama -> berhasil.
        $this->actingAs($this->admin)
            ->patch(route('admin.peminjaman.setujui', $pertama))
            ->assertSessionHas('success');

        // Admin mencoba menyetujui yang kedua di slot bentrok -> gagal dengan pesan flash error.
        $this->actingAs($this->admin)
            ->patch(route('admin.peminjaman.setujui', $kedua))
            ->assertRedirect(route('admin.peminjaman.show', $kedua))
            ->assertSessionHas('error', 'Tidak dapat disetujui: ruang/alat sudah terpakai oleh peminjaman lain.');

        $this->assertSame(Peminjaman::STATUS_DIAJUKAN, $kedua->fresh()->status);
    }

    public function test_admin_menolak_pengajuan_wajib_mengisi_catatan(): void
    {
        $peminjaman = Peminjaman::factory()->create([
            'status' => Peminjaman::STATUS_DIAJUKAN,
        ]);

        // Tanpa catatan -> error validasi.
        $this->actingAs($this->admin)
            ->patch(route('admin.peminjaman.tolak', $peminjaman), ['catatan_admin' => ''])
            ->assertSessionHasErrors(['catatan_admin' => 'Alasan penolakan wajib diisi.']);

        // Dengan catatan valid -> ditolak.
        $this->actingAs($this->admin)
            ->patch(route('admin.peminjaman.tolak', $peminjaman), [
                'catatan_admin' => 'Ruang dipakai ujian praktikum angkatan 2024.',
            ])
            ->assertRedirect(route('admin.peminjaman.show', $peminjaman))
            ->assertSessionHas('success');

        $peminjaman->refresh();
        $this->assertSame(Peminjaman::STATUS_DITOLAK, $peminjaman->status);
        $this->assertSame('Ruang dipakai ujian praktikum angkatan 2024.', $peminjaman->catatan_admin);
        $this->assertSame($this->admin->id, $peminjaman->diproses_oleh);
    }

    public function test_transisi_status_tidak_valid_ditolak_dengan_403(): void
    {
        $diajukan = Peminjaman::factory()->create(['status' => Peminjaman::STATUS_DIAJUKAN]);

        // Tidak boleh langsung serahkan atau kembalikan dari status diajukan.
        $this->actingAs($this->admin)->patch(route('admin.peminjaman.serahkan', $diajukan))->assertForbidden();
        $this->actingAs($this->admin)->patch(route('admin.peminjaman.kembalikan', $diajukan))->assertForbidden();
    }

    public function test_mahasiswa_tidak_dapat_mengakses_halaman_dan_aksi_admin_peminjaman(): void
    {
        $peminjaman = Peminjaman::factory()->create(['status' => Peminjaman::STATUS_DIAJUKAN]);

        $this->actingAs($this->mahasiswa)->get(route('admin.peminjaman.index'))->assertForbidden();
        $this->actingAs($this->mahasiswa)->patch(route('admin.peminjaman.setujui', $peminjaman))->assertForbidden();
        $this->actingAs($this->mahasiswa)->patch(route('admin.peminjaman.tolak', $peminjaman), [
            'catatan_admin' => 'Coba tolak sendiri.',
        ])->assertForbidden();
    }

    public function test_admin_dapat_mencari_dan_memfilter_daftar_peminjaman(): void
    {
        $mhsTarget = User::factory()->create(['name' => 'Andi Pratama', 'nim' => 'D121241999']);
        $cocok = Peminjaman::factory()->disetujui($this->admin)->create([
            'user_id' => $mhsTarget->id,
            'tanggal' => '2026-12-01',
        ]);
        Peminjaman::factory()->create(['tanggal' => '2026-12-10']);

        $response = $this->actingAs($this->admin)->get(route('admin.peminjaman.index', [
            'q' => 'D121241999',
            'status' => Peminjaman::STATUS_DISETUJUI,
            'dari' => '2026-12-01',
            'sampai' => '2026-12-05',
        ]));

        $response->assertOk()->assertViewHas('peminjaman', function ($daftar) use ($cocok) {
            return $daftar->total() === 1 && $daftar->first()->id === $cocok->id;
        });
    }
}
