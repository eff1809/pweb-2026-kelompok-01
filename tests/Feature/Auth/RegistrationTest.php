<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'nim' => 'D121241087',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registrasi_selalu_membuat_mahasiswa_walau_peran_disusupkan(): void
    {
        // LAKUKAN: kirim form registrasi + isian 'peran' => 'admin' yang disusupkan
        $this->post('/register', [
            'name' => 'Penyusup',
            'nim' => 'D121249999',
            'email' => 'penyusup@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'peran' => 'admin',          // ← isian palsu dari pengguna nakal
        ]);

        // PERIKSA: ambil user yang baru dibuat dari database
        $user = User::where('email', 'penyusup@example.com')->first();

        // PERIKSA: pastikan perannya tetap mahasiswa, bukan admin
        $this->assertTrue($user->isMahasiswa());
    }

    public function test_nim_yang_sudah_terdaftar_ditolak(): void
    {
        // SIAPKAN: sudah ada mahasiswa dengan NIM D121241087
        User::factory()->create(['nim' => 'D121241087']);

        // LAKUKAN: orang lain mendaftar dengan NIM sama, tapi huruf kecil
        $response = $this->post('/register', [
            'name' => 'Mahasiswa Kedua',
            'nim' => 'd121241087',
            'email' => 'kedua@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // PERIKSA: ada pesan error untuk isian 'nim'
        $response->assertSessionHasErrors('nim');

        // PERIKSA: pendaftar TIDAK berhasil login
        $this->assertGuest();
    }
}
