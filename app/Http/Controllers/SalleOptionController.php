<?php

namespace App\Http\Controllers;

use App\Models\Salle;
use App\Models\SalleOption;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalleOptionController extends MatrixAwareController
{
    public function index(Salle $salle)
    {
        $this->enforcePermission('salles', 'list', 'view');

        return view('salles.options', [
            'title' => 'Options salle',
            'salle' => $salle,
            'options' => $salle->options()->latest()->get(),
        ]);
    }

    public function store(Request $request, Salle $salle)
    {
        $this->enforcePermission('salles', 'update', 'update');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'note' => ['nullable', 'string'],
        ]);

        $validated['salle_id'] = $salle->id;
        SalleOption::query()->create($validated);

        return redirect()->route('salles.options.index', $salle)->with('success', 'Option ajoutee a la salle.');
    }

    public function update(Request $request, Salle $salle, SalleOption $option)
    {
        $this->enforcePermission('salles', 'update', 'update');

        abort_if((int) $option->salle_id !== (int) $salle->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'note' => ['nullable', 'string'],
        ]);

        $option->update($validated);

        return redirect()->route('salles.options.index', $salle)->with('success', 'Option salle mise a jour.');
    }

    public function destroy(Salle $salle, SalleOption $option)
    {
        $this->enforcePermission('salles', 'update', 'update');

        abort_if((int) $option->salle_id !== (int) $salle->id, 404);

        $option->delete();

        return redirect()->route('salles.options.index', $salle)->with('success', 'Option salle supprimee.');
    }
}
