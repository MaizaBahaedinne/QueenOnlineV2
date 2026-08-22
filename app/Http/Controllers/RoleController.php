<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        return view('roles.index', [
            'title' => 'Roles utilisateurs',
            'roles' => Role::query()->withCount('users')->orderBy('id')->get(),
            'users' => User::query()->with('role')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:roles,slug'],
            'description' => ['nullable', 'string'],
        ]);

        Role::query()->create($validated);

        return redirect()->route('roles.index')->with('success', 'Role cree.');
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('roles', 'slug')->ignore($role->id)],
            'description' => ['nullable', 'string'],
        ]);

        $role->update($validated);

        return redirect()->route('roles.index')->with('success', 'Role mis a jour.');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return redirect()->route('roles.index')->with('error', 'Impossible de supprimer un role assigne a des utilisateurs.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role supprime.');
    }

    public function updateUserRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => ['nullable', 'exists:roles,id'],
        ]);

        $user->update([
            'role_id' => $validated['role_id'] ?? null,
        ]);

        return redirect()->route('roles.index')->with('success', 'Role utilisateur mis a jour.');
    }
}
