<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'auth', 'role:admin'])
            ->get('/_test/khusus-admin', fn () => 'ok');
    }

    public function test_admin_bisa_membuka_halaman_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/_test/khusus-admin')
            ->assertOk();
    }

    public function test_mahasiswa_ditolak_dari_halaman_admin(): void
    {
        $mahasiswa = User::factory()->create();

        $this->actingAs($mahasiswa)
            ->get('/_test/khusus-admin')
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/_test/khusus-admin')
            ->assertRedirect('/login');
    }
}