<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleFeature;
use App\Models\Role;
use App\Models\RoleFeaturePermission;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $structure = [
            [
                'module' => ['name' => 'Utilisateurs', 'slug' => 'users', 'description' => 'Gestion des utilisateurs', 'sort_order' => 10],
                'features' => [
                    ['name' => 'Liste utilisateurs', 'slug' => 'list', 'sort_order' => 10],
                    ['name' => 'Creation utilisateur', 'slug' => 'create', 'sort_order' => 20],
                    ['name' => 'Edition utilisateur', 'slug' => 'update', 'sort_order' => 30],
                    ['name' => 'Suppression utilisateur', 'slug' => 'delete', 'sort_order' => 40],
                ],
            ],
            [
                'module' => ['name' => 'Clients', 'slug' => 'clients', 'description' => 'Gestion de la clientele', 'sort_order' => 20],
                'features' => [
                    ['name' => 'Liste clients', 'slug' => 'list', 'sort_order' => 10],
                    ['name' => 'Creation client', 'slug' => 'create', 'sort_order' => 20],
                    ['name' => 'Edition client', 'slug' => 'update', 'sort_order' => 30],
                    ['name' => 'Suppression client', 'slug' => 'delete', 'sort_order' => 40],
                ],
            ],
            [
                'module' => ['name' => 'Salles', 'slug' => 'salles', 'description' => 'Gestion des salles', 'sort_order' => 30],
                'features' => [
                    ['name' => 'Liste salles', 'slug' => 'list', 'sort_order' => 10],
                    ['name' => 'Creation salle', 'slug' => 'create', 'sort_order' => 20],
                    ['name' => 'Edition salle', 'slug' => 'update', 'sort_order' => 30],
                    ['name' => 'Suppression salle', 'slug' => 'delete', 'sort_order' => 40],
                ],
            ],
            [
                'module' => ['name' => 'Reservations', 'slug' => 'reservations', 'description' => 'Gestion des reservations', 'sort_order' => 40],
                'features' => [
                    ['name' => 'Liste reservations', 'slug' => 'list', 'sort_order' => 10],
                    ['name' => 'Creation reservation', 'slug' => 'create', 'sort_order' => 20],
                    ['name' => 'Edition reservation', 'slug' => 'update', 'sort_order' => 30],
                    ['name' => 'Suppression reservation', 'slug' => 'delete', 'sort_order' => 40],
                ],
            ],
            [
                'module' => ['name' => 'Paiements', 'slug' => 'payments', 'description' => 'Gestion des paiements', 'sort_order' => 50],
                'features' => [
                    ['name' => 'Liste paiements', 'slug' => 'list', 'sort_order' => 10],
                    ['name' => 'Creation paiement', 'slug' => 'create', 'sort_order' => 20],
                    ['name' => 'Edition paiement', 'slug' => 'update', 'sort_order' => 30],
                    ['name' => 'Suppression paiement', 'slug' => 'delete', 'sort_order' => 40],
                ],
            ],
        ];

        foreach ($structure as $item) {
            $module = Module::query()->updateOrCreate(
                ['slug' => $item['module']['slug']],
                $item['module'] + ['is_active' => true]
            );

            foreach ($item['features'] as $featureData) {
                $feature = ModuleFeature::query()->updateOrCreate(
                    [
                        'module_id' => $module->id,
                        'slug' => $featureData['slug'],
                    ],
                    $featureData + [
                        'module_id' => $module->id,
                        'is_active' => true,
                    ]
                );

                foreach (Role::query()->get() as $role) {
                    $isAdmin = $role->slug === 'admin';
                    $isManager = $role->slug === 'manager';

                    RoleFeaturePermission::query()->updateOrCreate(
                        [
                            'role_id' => $role->id,
                            'module_feature_id' => $feature->id,
                        ],
                        [
                            'can_view' => $isAdmin || $isManager,
                            'can_create' => $isAdmin || $isManager,
                            'can_update' => $isAdmin || $isManager,
                            'can_delete' => $isAdmin,
                        ]
                    );
                }
            }
        }
    }
}
