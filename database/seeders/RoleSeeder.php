<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrateur du système'],
            ['name' => 'Manager', 'slug' => 'manager', 'description' => 'Gestionnaire de réservation'],
            ['name' => 'Client', 'slug' => 'client', 'description' => 'Client final'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                $role
            );
        }
    }
}
