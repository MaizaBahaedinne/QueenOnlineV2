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
                'module' => ['name' => 'Services salle', 'slug' => 'services-salle', 'description' => 'Gestion des entrees, retours, feedbacks et affectations pour reservations salle', 'sort_order' => 45],
                'features' => [
                    ['name' => 'Liste services salle', 'slug' => 'list', 'sort_order' => 10],
                    ['name' => 'Creation service salle', 'slug' => 'create', 'sort_order' => 20],
                    ['name' => 'Edition service salle', 'slug' => 'update', 'sort_order' => 30],
                    ['name' => 'Suppression service salle', 'slug' => 'delete', 'sort_order' => 40],
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
            [
                'module' => ['name' => 'Troupe musicale', 'slug' => 'troupe-musicale', 'description' => 'Gestion des troupes musicales et packs', 'sort_order' => 60],
                'features' => [
                    ['name' => 'Liste troupes musicales', 'slug' => 'list', 'sort_order' => 10],
                    ['name' => 'Creation troupe musicale', 'slug' => 'create', 'sort_order' => 20],
                    ['name' => 'Edition troupe musicale', 'slug' => 'update', 'sort_order' => 30],
                    ['name' => 'Suppression troupe musicale', 'slug' => 'delete', 'sort_order' => 40],
                    ['name' => 'Liste packs troupe musicale', 'slug' => 'list-pack', 'sort_order' => 50],
                    ['name' => 'Creation pack troupe musicale', 'slug' => 'create-pack', 'sort_order' => 60],
                    ['name' => 'Edition pack troupe musicale', 'slug' => 'update-pack', 'sort_order' => 70],
                    ['name' => 'Suppression pack troupe musicale', 'slug' => 'delete-pack', 'sort_order' => 80],
                ],
            ],
            [
                'module' => ['name' => 'Photographe', 'slug' => 'photographe', 'description' => 'Gestion des photographes et packs', 'sort_order' => 70],
                'features' => [
                    ['name' => 'Liste photographes', 'slug' => 'list', 'sort_order' => 10],
                    ['name' => 'Creation photographe', 'slug' => 'create', 'sort_order' => 20],
                    ['name' => 'Edition photographe', 'slug' => 'update', 'sort_order' => 30],
                    ['name' => 'Suppression photographe', 'slug' => 'delete', 'sort_order' => 40],
                    ['name' => 'Liste packs photographe', 'slug' => 'list-pack', 'sort_order' => 50],
                    ['name' => 'Creation pack photographe', 'slug' => 'create-pack', 'sort_order' => 60],
                    ['name' => 'Edition pack photographe', 'slug' => 'update-pack', 'sort_order' => 70],
                    ['name' => 'Suppression pack photographe', 'slug' => 'delete-pack', 'sort_order' => 80],
                ],
            ],
            [
                'module' => ['name' => 'Chanteur', 'slug' => 'chanteur', 'description' => 'Gestion des chanteurs', 'sort_order' => 80],
                'features' => [
                    ['name' => 'Liste chanteurs', 'slug' => 'list', 'sort_order' => 10],
                    ['name' => 'Creation chanteur', 'slug' => 'create', 'sort_order' => 20],
                    ['name' => 'Edition chanteur', 'slug' => 'update', 'sort_order' => 30],
                    ['name' => 'Suppression chanteur', 'slug' => 'delete', 'sort_order' => 40],
                ],
            ],
            [
                'module' => ['name' => 'Notaire', 'slug' => 'notaire', 'description' => 'Gestion des notaires', 'sort_order' => 90],
                'features' => [
                    ['name' => 'Liste notaires', 'slug' => 'list', 'sort_order' => 10],
                    ['name' => 'Creation notaire', 'slug' => 'create', 'sort_order' => 20],
                    ['name' => 'Edition notaire', 'slug' => 'update', 'sort_order' => 30],
                    ['name' => 'Suppression notaire', 'slug' => 'delete', 'sort_order' => 40],
                ],
            ],
            [
                'module' => ['name' => 'Animation', 'slug' => 'animation', 'description' => 'Gestion des services d animation', 'sort_order' => 100],
                'features' => [
                    ['name' => 'Liste animations', 'slug' => 'list', 'sort_order' => 10],
                    ['name' => 'Creation animation', 'slug' => 'create', 'sort_order' => 20],
                    ['name' => 'Edition animation', 'slug' => 'update', 'sort_order' => 30],
                    ['name' => 'Suppression animation', 'slug' => 'delete', 'sort_order' => 40],
                ],
            ],
            [
                'module' => ['name' => 'Voiture', 'slug' => 'voiture', 'description' => 'Gestion des voitures', 'sort_order' => 110],
                'features' => [
                    ['name' => 'Liste voitures', 'slug' => 'list', 'sort_order' => 10],
                    ['name' => 'Creation voiture', 'slug' => 'create', 'sort_order' => 20],
                    ['name' => 'Edition voiture', 'slug' => 'update', 'sort_order' => 30],
                    ['name' => 'Suppression voiture', 'slug' => 'delete', 'sort_order' => 40],
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
