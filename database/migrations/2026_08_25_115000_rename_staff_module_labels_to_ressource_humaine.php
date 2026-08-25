<?php

use App\Models\Module;
use App\Models\ModuleFeature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('modules') || ! Schema::hasTable('module_features')) {
            return;
        }

        $module = Module::query()->where('slug', 'staff')->first();
        if (! $module) {
            return;
        }

        $module->forceFill([
            'name' => 'Ressource Humaine',
            'description' => 'Gestion des ressources humaines et des departements',
        ])->save();

        $featureLabels = [
            'list' => 'Liste ressources humaines',
            'create' => 'Creation ressources humaines',
            'update' => 'Edition ressources humaines',
            'delete' => 'Suppression ressources humaines',
        ];

        foreach ($featureLabels as $slug => $name) {
            ModuleFeature::query()
                ->where('module_id', $module->id)
                ->where('slug', $slug)
                ->update(['name' => $name]);
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

        $module->forceFill([
            'name' => 'Staff',
            'description' => 'Gestion du personnel et des departements',
        ])->save();

        $featureLabels = [
            'list' => 'Liste staff',
            'create' => 'Creation staff',
            'update' => 'Edition staff',
            'delete' => 'Suppression staff',
        ];

        foreach ($featureLabels as $slug => $name) {
            ModuleFeature::query()
                ->where('module_id', $module->id)
                ->where('slug', $slug)
                ->update(['name' => $name]);
        }
    }
};
