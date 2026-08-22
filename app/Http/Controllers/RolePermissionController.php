<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Role;
use App\Models\RoleFeaturePermission;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    public function index()
    {
        $roles = Role::query()->orderBy('id')->get();

        $modules = Module::query()
            ->with(['features' => function ($query) {
                $query->orderBy('sort_order')->orderBy('id');
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $permissions = RoleFeaturePermission::query()->get()
            ->keyBy(function (RoleFeaturePermission $permission) {
                return $permission->role_id . '_' . $permission->module_feature_id;
            });

        return view('permissions.matrix', [
            'title' => 'Matrice des autorisations',
            'roles' => $roles,
            'modules' => $modules,
            'permissions' => $permissions,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'matrix' => ['array'],
            'matrix.*' => ['array'],
            'matrix.*.*' => ['array'],
        ]);

        $matrix = $validated['matrix'] ?? [];

        foreach ($matrix as $roleId => $featurePermissions) {
            foreach ($featurePermissions as $featureId => $actions) {
                RoleFeaturePermission::query()->updateOrCreate(
                    [
                        'role_id' => (int) $roleId,
                        'module_feature_id' => (int) $featureId,
                    ],
                    [
                        'can_view' => array_key_exists('can_view', $actions),
                        'can_create' => array_key_exists('can_create', $actions),
                        'can_update' => array_key_exists('can_update', $actions),
                        'can_delete' => array_key_exists('can_delete', $actions),
                    ]
                );
            }
        }

        return redirect()->route('permissions.matrix')->with('success', 'Matrice des autorisations mise a jour.');
    }
}
