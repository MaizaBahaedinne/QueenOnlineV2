<?php

namespace App\Http\Controllers;

use App\Models\Salle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalleController extends MatrixAwareController
{
    public function index()
    {
        $this->enforcePermission('salles', 'list', 'view');

        return view('salles.index', [
            'salles' => Salle::query()->latest()->get(),
        ]);
    }

    public function create()
    {
        return redirect()->route('salles.index');
    }

    public function store(Request $request)
    {
        $this->enforcePermission('salles', 'create', 'create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'price_per_day' => ['required', 'numeric', 'min:0'],
        ]);

        Salle::create($validated);

        return redirect()->route('salles.index')->with('success', 'Salle creee.');
    }

    public function update(Request $request, Salle $salle)
    {
        $this->enforcePermission('salles', 'update', 'update');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'price_per_day' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $salle->update($validated);

        return redirect()->route('salles.index')->with('success', 'Salle mise a jour.');
    }

    public function destroy(Salle $salle)
    {
        $this->enforcePermission('salles', 'delete', 'delete');

        $salle->delete();

        return redirect()->route('salles.index')->with('success', 'Salle supprimee.');
    }
}
