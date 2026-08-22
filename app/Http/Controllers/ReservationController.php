<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Reservation;
use App\Models\Salle;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReservationController extends MatrixAwareController
{
    public function index()
    {
        $this->enforcePermission('reservations', 'list', 'view');

        return view('reservations.index', [
            'title' => 'Reservations',
            'reservations' => Reservation::query()->with(['client', 'salle'])->latest()->get(),
            'clients' => Client::query()->orderBy('name')->get(),
            'salles' => Salle::query()->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return redirect()->route('reservations.index');
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

        return redirect()->route('reservations.index')->with('success', 'Reservation creee.');
    }

    public function update(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'salle_id' => ['required', 'exists:salles,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $reservation->update($validated);

        return redirect()->route('reservations.index')->with('success', 'Reservation mise a jour.');
    }

    public function destroy(Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'delete', 'delete');

        $reservation->delete();

        return redirect()->route('reservations.index')->with('success', 'Reservation supprimee.');
    }
}
