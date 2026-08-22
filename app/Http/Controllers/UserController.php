<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends MatrixAwareController
{
    public function index()
    {
        $this->enforcePermission('users', 'list', 'view');

        return view('users.index', [
            'title' => 'Utilisateurs',
            'users' => User::query()->with('role')->latest()->get(),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return redirect()->route('users.index');
    }

    public function store(Request $request)
    {
        $this->enforcePermission('users', 'create', 'create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'role_id' => ['nullable', 'exists:roles,id'],
        ]);

        User::create([
            ...$validated,
            'password' => bcrypt($validated['password']),
        ]);

        return redirect()->route('users.index')->with('success', 'Utilisateur cree.');
    }

    public function update(Request $request, User $user)
    {
        $this->enforcePermission('users', 'update', 'update');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'role_id' => ['nullable', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'role_id' => $validated['role_id'] ?? null,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = bcrypt($validated['password']);
        }

        $user->update($payload);

        return redirect()->route('users.index')->with('success', 'Utilisateur mis a jour.');
    }

    public function destroy(User $user)
    {
        $this->enforcePermission('users', 'delete', 'delete');

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur supprime.');
    }
}
