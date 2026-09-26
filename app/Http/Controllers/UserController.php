<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends MatrixAwareController
{
    public function index()
    {
        $this->enforcePermission('users', 'list', 'view');

        $users = User::query()->with('role')->latest()->get();

        return view('users.index', [
            'title' => 'Utilisateurs',
            'users' => $users,
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

    public function impersonate(Request $request, User $user)
    {
        $currentUser = Auth::user();

        abort_unless($currentUser instanceof User && $currentUser->isSuperAdmin(), 403, 'Action reservee au super admin.');
        abort_if($currentUser->id === $user->id, 422, 'Vous etes deja connecte avec ce compte.');

        $request->session()->put('impersonator_user_id', $currentUser->id);
        $request->session()->put('impersonating_user_id', $user->id);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Connexion activee en tant que ' . $user->name . '.');
    }

    public function stopImpersonation(Request $request)
    {
        $currentUser = Auth::user();

        $impersonatorId = (int) $request->session()->pull('impersonator_user_id', 0);
        $request->session()->forget('impersonating_user_id');

        abort_if($impersonatorId <= 0, 404, 'Aucune session d impersonation active.');

        Auth::loginUsingId($impersonatorId);
        $request->session()->regenerate();

        return redirect()->route('users.index')->with('success', 'Retour a votre compte admin.');
    }
}
