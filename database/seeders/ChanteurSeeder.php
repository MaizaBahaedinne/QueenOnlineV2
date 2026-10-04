<?php

namespace Database\Seeders;

use App\Models\ServiceModuleItem;
use Illuminate\Database\Seeder;

class ChanteurSeeder extends Seeder
{
    public function run(): void
    {
        $chanteurs = [
            ['name' => 'ZAZA Show', 'base_price' => 3500],
            ['name' => 'Anis letaif', 'base_price' => 2500],
            ['name' => 'Maryem Noureddine', 'base_price' => 3000],
            ['name' => 'Nour eddine Kahlaoui', 'base_price' => 2500],
            ['name' => 'Faouzi Ben Gamra', 'base_price' => 4000],
            ['name' => 'Emna Fakher', 'base_price' => 3500],
            ['name' => 'Houssine el efrit', 'base_price' => 2500],
            ['name' => 'Hichem nagati', 'base_price' => 2000],
            ['name' => 'Manel amara', 'base_price' => 3500],
            ['name' => 'Fahmi riahi', 'base_price' => 4000],
            ['name' => 'Sofien zaydi', 'base_price' => 3000],
            ['name' => 'Zied gharsa', 'base_price' => 6000],
            ['name' => 'Chirine lajmi', 'base_price' => 4000],
            ['name' => 'Rayen yousef', 'base_price' => 3000],
            ['name' => 'Mostpha Dalegi', 'base_price' => 3500],
            ['name' => 'Zahra Fares', 'base_price' => 2000],
            ['name' => 'Chamseddin becha', 'base_price' => 7000],
            ['name' => 'Mohsen cherif', 'base_price' => 3500],
            ['name' => 'Moez troudi', 'base_price' => 1700],
            ['name' => 'Sirine miled', 'base_price' => 2000],
            ['name' => 'Rana Zarrouk', 'base_price' => 2500],
            ['name' => 'Najla Tounsia', 'base_price' => 3500],
            ['name' => 'Nadia khaless', 'base_price' => 4000],
            ['name' => 'Nour chiba', 'base_price' => 4000],
        ];

        foreach ($chanteurs as $chanteur) {
            ServiceModuleItem::query()->updateOrCreate(
                [
                    'module_slug' => 'chanteur',
                    'name' => $chanteur['name'],
                ],
                [
                    'phone' => null,
                    'base_price' => $chanteur['base_price'],
                    'status' => 'active',
                    'notes' => null,
                ]
            );
        }
    }
}
