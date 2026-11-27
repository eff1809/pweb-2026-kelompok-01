<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\Ruang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class PeminjamanTest extends TestCase
{
    use RefreshDatabase;

    private User $mahasiswa;

    private User $mahasiswaLain;

    private User $admin;

    private Ruang $ruang;

    private Alat $alat;

    protected function setUp(): void
    {
        parent::setUp();

        // View sementara dipakai selama view asli belum dibuat tim frontend.
        View::addLocation(base_path('tests/Fixtures/views'));
        $this->withoutVite();

        $this->mahasiswa = User::factory()->create();
        $this->mahasiswaLain = User::factory()->create();
        $this->admin = User::factory()->admin()->create();
        $this->ruang = Ruang::factory()->create(['status' => Ruang::STATUS_TERSEDIA]);
        $this->alat = Alat::factory()->create([
            'jumlah_total' => 5,
            'kondisi' => Alat::KONDISI_BAIK,
        ]);
    }

    private function dataValid(array $ubah = []): array
    {
        return array_merge([
            'ruang_id' => $this->ruang->id,
            'tanggal' => now()->addDays(2)->toDateString(),
            'jam_mulai' => '09:00',
            'jam_selesai' => '11:00',
            'keperluan' => 'Praktikum Pemrograman Web modul Laravel.',
            'alat' => [
                ['alat_id' => $this->alat->id, 'jumlah' => 2],
            ],
        ], $ubah);
    }

    public function test_mahasiswa_dapat_mengajukan_peminjaman_ruang_dan_alat(): void
    {
        $response = $this->actingAs($this->mahasiswa)
            ->post(route('peminjaman.store'), $this->dataValid());

        $peminjaman = Peminjaman::first();

        $this->assertNotNull($peminjaman);
        $response->assertRedirect(route('peminjaman.show', $peminjaman))
            ->assertSessionHas('success');

        $this->assertSame($this->mahasiswa->id, $peminjaman->user_id);
        $this->assertSame(Peminjaman::STATUS_DIAJUKAN, $peminjaman->status);
        $this->assertDatabaseHas('peminjaman_alat', [
            'peminjaman_id' => $peminjaman->id,
            'alat_id' => $this->alat->id,
            'jumlah' => 2,
        ]);
    }

    public function test_mahasiswa_dapat_mengajukan_hanya_alat_tanpa_ruang(): void
    {
        $response = $this->actingAs($this->mahasiswa)
            ->post(route('peminjaman.store'), $this->dataValid(['ruang_id' => null]));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('peminjaman', [
            'user_id' => $this->mahasiswa->id,
            'ruang_id' => null,
            'status' => Peminjaman::STATUS_DIAJUKAN,
        ]);
    }

    public function test_pengajuan_tanpa_ruang_dan_tanpa_alat_ditolak(): void
    {
        $response = $this->actingAs($this->mahasiswa)
            ->post(route('peminjaman.store'), $this->dataValid([
                'ruang_id' => null,
                'alat' => [],
            ]));

        $response->assertSessionHasErrors(['ruang_id', 'alat']);
        $this->assertDatabaseCount('peminjaman', 0);
    }

    public function test_validasi_tanggal_lampau_dan_melebihi_30_hari(): void
    {
        $this->actingAs($this->mahasiswa)
            ->post(route('peminjaman.store'), $this->dataValid([
                'tanggal' => now()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors(['tanggal' => 'Tanggal tidak boleh sebelum hari ini.']);

        $this->actingAs($this->mahasiswa)
            ->post(route('peminjaman.store'), $this->dataValid([
                'tanggal' => now()->addDays(31)->toDateString(),
            ]))
            ->assertSessionHasErrors(['tanggal' => 'Pengajuan paling lambat 30 hari ke depan.']);
    }

    public function test_validasi_durasi_maksimal_8_jam_dan_jam_operasional(): void
    {
        $this->actingAs($this->mahasiswa)
            ->post(route('peminjaman.store'), $this->dataValid([
                'jam_mulai' => '08:00',
                'jam_selesai' => '17:00',
            ]))
            ->assertSessionHasErrors(['jam_selesai' => 'Durasi peminjaman maksimal 8 jam.']);

        $this->actingAs($this->mahasiswa)
            ->post(route('peminjaman.store'), $this->dataValid([
                'jam_mulai' => '06:30',
                'jam_selesai' => '08:30',
            ]))
            ->assertSessionHasErrors(['jam_mulai' => 'Peminjaman hanya pada jam operasional 07.00–18.00.']);
    }

    public function test_validasi_bentrok_ruang_dan_rentang_bersentuhan_diizinkan(): void
    {
        $tanggal = now()->addDays(2)->toDateString();

        Peminjaman::factory()->disetujui($this->admin)->create([
            'ruang_id' => $this->ruang->id,
            'tanggal' => $tanggal,
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '11:00:00',
        ]);

        // Beririsan 10.00–12.00 harus ditolak.
        $this->actingAs($this->mahasiswa)
            ->post(route('peminjaman.store'), $this->dataValid([
                'tanggal' => $tanggal,
                'jam_mulai' => '10:00',
                'jam_selesai' => '12:00',
                'alat' => [],
            ]))
            ->assertSessionHasErrors('ruang_id');

        // Hanya bersentuhan 11.00–13.00 harus diperbolehkan.
        $this->actingAs($this->mahasiswa)
            ->post(route('peminjaman.store'), $this->dataValid([
                'tanggal' => $tanggal,
                'jam_mulai' => '11:00',
                'jam_selesai' => '13:00',
                'alat' => [],
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_validasi_stok_alat_tidak_mencukupi_dan_alat_rusak_berat(): void
    {
        $tanggal = now()->addDays(2)->toDateString();

        $aktif = Peminjaman::factory()->dipinjam($this->admin)->create([
            'ruang_id' => null,
            'tanggal' => $tanggal,
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '11:00:00',
        ]);
        $aktif->alat()->attach($this->alat->id, ['jumlah' => 4]);

        // Stok total 5, terpakai 4, sisa 1 -> minta 2 harus ditolak.
        $this->actingAs($this->mahasiswa)
            ->post(route('peminjaman.store'), $this->dataValid([
                'ruang_id' => null,
                'tanggal' => $tanggal,
                'alat' => [['alat_id' => $this->alat->id, 'jumlah' => 2]],
            ]))
            ->assertSessionHasErrors('alat.0.jumlah');

        $alatRusak = Alat::factory()->kondisi(Alat::KONDISI_RUSAK_BERAT)->create();

        $this->actingAs($this->mahasiswa)
            ->post(route('peminjaman.store'), $this->dataValid([
                'ruang_id' => null,
                'alat' => [['alat_id' => $alatRusak->id, 'jumlah' => 1]],
            ]))
            ->assertSessionHasErrors('alat.0.alat_id');
    }

    public function test_mahasiswa_dapat_mengubah_dan_membatalkan_pengajuan_miliknya_saat_diajukan(): void
    {
        $peminjaman = Peminjaman::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'ruang_id' => $this->ruang->id,
            'status' => Peminjaman::STATUS_DIAJUKAN,
        ]);

        $this->actingAs($this->mahasiswa)
            ->put(route('peminjaman.update', $peminjaman), $this->dataValid([
                'keperluan' => 'Praktikum lanjutan basis data terdistribusi.',
            ]))
            ->assertRedirect(route('peminjaman.show', $peminjaman))
            ->assertSessionHasNoErrors();

        $this->assertSame('Praktikum lanjutan basis data terdistribusi.', $peminjaman->fresh()->keperluan);

        $this->actingAs($this->mahasiswa)
            ->patch(route('peminjaman.batal', $peminjaman))
            ->assertRedirect(route('peminjaman.show', $peminjaman));

        $this->assertSame(Peminjaman::STATUS_DIBATALKAN, $peminjaman->fresh()->status);
    }

    public function test_policy_melarang_mahasiswa_melihat_mengubah_atau_membatalkan_milik_orang_lain(): void
    {
        $peminjaman = Peminjaman::factory()->create([
            'user_id' => $this->mahasiswa->id,
            'ruang_id' => $this->ruang->id,
            'status' => Peminjaman::STATUS_DIAJUKAN,
        ]);

        $this->actingAs($this->mahasiswaLain)->get(route('peminjaman.show', $peminjaman))->assertForbidden();
        $this->actingAs($this->mahasiswaLain)->get(route('peminjaman.edit', $peminjaman))->assertForbidden();
        $this->actingAs($this->mahasiswaLain)->put(route('peminjaman.update', $peminjaman), $this->dataValid())->assertForbidden();
        $this->actingAs($this->mahasiswaLain)->patch(route('peminjaman.batal', $peminjaman))->assertForbidden();

        // Admin tetap boleh melihat detail peminjaman via peminjaman.show.
        $this->actingAs($this->admin)->get(route('peminjaman.show', $peminjaman))->assertOk();
    }

    public function test_mahasiswa_tidak_dapat_mengubah_atau_membatalkan_jika_sudah_disetujui(): void
    {
        $peminjaman = Peminjaman::factory()->disetujui($this->admin)->create([
            'user_id' => $this->mahasiswa->id,
            'ruang_id' => $this->ruang->id,
        ]);

        $this->actingAs($this->mahasiswa)->get(route('peminjaman.edit', $peminjaman))->assertForbidden();
        $this->actingAs($this->mahasiswa)->put(route('peminjaman.update', $peminjaman), $this->dataValid())->assertForbidden();
        $this->actingAs($this->mahasiswa)->patch(route('peminjaman.batal', $peminjaman))->assertForbidden();
    }

    public function test_riwayat_peminjaman_mahasiswa_hanya_menampilkan_milik_sendiri(): void
    {
        $milikSendiri = Peminjaman::factory()->create(['user_id' => $this->mahasiswa->id]);
        Peminjaman::factory()->create(['user_id' => $this->mahasiswaLain->id]);

        $response = $this->actingAs($this->mahasiswa)->get(route('peminjaman.index'));

        $response->assertOk()->assertViewHas('peminjaman', function ($daftar) use ($milikSendiri) {
            return $daftar->total() === 1 && $daftar->first()->id === $milikSendiri->id;
        });
    }
}
