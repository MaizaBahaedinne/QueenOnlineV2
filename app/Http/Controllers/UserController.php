<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends MatrixAwareController
{
    public function index()
    {
        $this->enforcePermission('users', 'list', 'view');

        return view('users.index', [
            'users' => User::query()->latest()->get(),
        ]);
    }

    public function create()
    {
        $this->enforcePermission('users', 'create', 'create');

        return view('users.create');
    }

    public function store(Request $request)
    {
        $this->enforcePermission('users', 'create', 'create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            ...$validated,
            'password' => bcrypt($validated['password']),
        ]);

        return redirect()->route('users.index')->with('success', 'Utilisateur créé.');
    }
}
