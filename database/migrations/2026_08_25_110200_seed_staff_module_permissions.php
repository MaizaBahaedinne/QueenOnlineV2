<?php

use App\Models\Module;
use App\Models\ModuleFeature;
use App\Models\Role;
use App\Models\RoleFeaturePermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('modules') || ! Schema::hasTable('module_features') || ! Schema::hasTable('roles') || ! Schema::hasTable('role_feature_permissions')) {
            return;
        }

        $module = Module::query()->firstOrCreate(
            ['slug' => 'staff'],
            [
                'name' => 'Ressource Humaine',
                'description' => 'Gestion du personnel et des departements',
                'sort_order' => 35,
                'is_active' => true,
            ]
        );

        $module->forceFill([
            'name' => 'Ressource Humaine',
            'description' => 'Gestion des ressources humaines et des departements',
        ])->save();

        $features = [
            ['name' => 'Liste ressources humaines', 'slug' => 'list', 'sort_order' => 10],
            ['name' => 'Creation ressources humaines', 'slug' => 'create', 'sort_order' => 20],
            ['name' => 'Edition ressources humaines', 'slug' => 'update', 'sort_order' => 30],
            ['name' => 'Suppression ressources humaines', 'slug' => 'delete', 'sort_order' => 40],
        ];

        foreach ($features as $feature) {
            $featureRow = ModuleFeature::query()->firstOrCreate(
                ['module_id' => $module->id, 'slug' => $feature['slug']],
                [
                    'name' => $feature['name'],
                    'description' => null,
                    'sort_order' => $feature['sort_order'],
                    'is_active' => true,
                ]
            );

            $featureRow->forceFill([
                'name' => $feature['name'],
                'sort_order' => $feature['sort_order'],
            ])->save();

            foreach (Role::query()->get() as $role) {
                $isAdmin = $role->slug === 'admin';
                $isManager = $role->slug === 'manager';

                RoleFeaturePermission::query()->firstOrCreate(
                    ['role_id' => $role->id, 'module_feature_id' => $featureRow->id],
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

    public function down(): void
    {
        if (! Schema::hasTable('modules') || ! Schema::hasTable('module_features')) {
            return;
        }

        $module = Module::query()->where('slug', 'staff')->first();
        if (! $module) {
            return;
        }

        $featureIds = ModuleFeature::query()->where('module_id', $module->id)->pluck('id');
        if ($featureIds->isNotEmpty() && Schema::hasTable('role_feature_permissions')) {
            RoleFeaturePermission::query()->whereIn('module_feature_id', $featureIds)->delete();
        }

        ModuleFeature::query()->where('module_id', $module->id)->delete();
        $module->delete();
    }
};
