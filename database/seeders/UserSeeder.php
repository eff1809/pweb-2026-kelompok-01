<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin Laboratorium',
            'email' => 'admin@lab.test',
        ]);

        User::factory()->create([
            'name' => 'Andi Pratama',
            'nim' => 'D121241001',
            'email' => 'mahasiswa@lab.test',
        ]);

        User::factory(8)->create();
    }
}
