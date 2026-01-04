<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1 admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'age' => 30,
            'role' => 'admin',
        ]);

        // 5 student felhasználó létrehozása
        User::factory(5)->create();
    }
}
