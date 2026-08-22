<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RoleSeeder::class);

        User::query()->updateOrCreate(
            ['email' => 'admin@queenonline.test'],
            [
                'name' => 'Admin QueenOnline',
                'password' => bcrypt('password123'),
                'role_id' => 1,
                'status' => 'active',
            ]
        );
    }
}
