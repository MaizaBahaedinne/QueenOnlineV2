<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends MatrixAwareController
{
    public function index()
    {
        $this->enforcePermission('clients', 'list', 'view');

        return view('clients.index', [
            'clients' => Client::query()->latest()->get(),
        ]);
    }

    public function create()
    {
        $this->enforcePermission('clients', 'create', 'create');

        return view('clients.create');
    }

    public function store(Request $request)
    {
        $this->enforcePermission('clients', 'create', 'create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'cin' => ['nullable', 'string', 'max:50'],
        ]);

        Client::create($validated);

        return redirect()->route('clients.index')->with('success', 'Client créé.');
    }
}
