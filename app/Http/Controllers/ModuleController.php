<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\ModuleFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ModuleController extends Controller
{
    public function index()
    {
        return view('modules.index', [
            'title' => 'Modules',
            'modules' => Module::query()
                ->with('features')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function storeModule(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        Module::query()->create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::lower(Str::random(4)),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('modules.index')->with('success', 'Module ajoute.');
    }

    public function storeFeature(Request $request, Module $module)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        ModuleFeature::query()->create([
            'module_id' => $module->id,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']) . '-' . Str::lower(Str::random(4)),
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('modules.index')->with('success', 'Fonctionnalite ajoutee.');
    }

    public function toggleModule(Module $module)
    {
        $module->update(['is_active' => ! $module->is_active]);

        return redirect()->route('modules.index')->with('success', 'Etat du module mis a jour.');
    }

    public function toggleFeature(ModuleFeature $feature)
    {
        $feature->update(['is_active' => ! $feature->is_active]);

        return redirect()->route('modules.index')->with('success', 'Etat de la fonctionnalite mis a jour.');
    }
}
