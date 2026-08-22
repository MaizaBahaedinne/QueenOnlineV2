<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        return redirect()->route('clients.index');
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

        return redirect()->route('clients.index')->with('success', 'Client cree.');
    }

    public function update(Request $request, Client $client)
    {
        $this->enforcePermission('clients', 'update', 'update');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'cin' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $client->update($validated);

        return redirect()->route('clients.index')->with('success', 'Client mis a jour.');
    }

    public function destroy(Client $client)
    {
        $this->enforcePermission('clients', 'delete', 'delete');

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client supprime.');
    }
}
