<?php

namespace App\Http\Controllers;

use App\Models\Salle;
use Illuminate\Http\Request;

class SalleController extends Controller
{
    public function index()
    {
        return view('salles.index', [
            'salles' => Salle::query()->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('salles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'price_per_day' => ['required', 'numeric', 'min:0'],
        ]);

        Salle::create($validated);

        return redirect()->route('salles.index')->with('success', 'Salle créée.');
    }
}
