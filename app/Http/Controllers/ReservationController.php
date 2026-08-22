<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends MatrixAwareController
{
    public function index()
    {
        $this->enforcePermission('reservations', 'list', 'view');

        return view('reservations.index', [
            'reservations' => Reservation::query()->with(['client', 'salle'])->latest()->get(),
        ]);
    }

    public function create()
    {
        $this->enforcePermission('reservations', 'create', 'create');

        return view('reservations.create');
    }

    public function store(Request $request)
    {
        $this->enforcePermission('reservations', 'create', 'create');

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'salle_id' => ['required', 'exists:salles,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        Reservation::create($validated);

        return redirect()->route('reservations.index')->with('success', 'Réservation créée.');
    }
}
