<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Reservation;
use App\Models\Salle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
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
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
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

    public function availableSalles(Request $request)
    {
        $this->enforcePermission('reservations', 'create', 'create');

        $validated = $request->validate([
            'event_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'exclude_reservation_id' => ['nullable', 'integer', 'exists:reservations,id'],
        ]);

        $eventDate = $validated['event_date'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];
        $excludeReservationId = $validated['exclude_reservation_id'] ?? null;

        $salles = Salle::query()
            ->where('status', 'active')
            ->whereDoesntHave('reservations', function ($query) use ($eventDate, $startTime, $endTime, $excludeReservationId) {
                $query
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('start_date', '<=', $eventDate)
                    ->whereDate('end_date', '>=', $eventDate)
                    ->when($excludeReservationId, function ($subQuery) use ($excludeReservationId) {
                        $subQuery->where('id', '!=', $excludeReservationId);
                    })
                    ->where(function ($timeQuery) use ($startTime, $endTime) {
                        $timeQuery
                            ->whereNull('start_time')
                            ->orWhereNull('end_time')
                            ->orWhere(function ($overlapQuery) use ($startTime, $endTime) {
                                $overlapQuery
                                    ->where('start_time', '<', $endTime)
                                    ->where('end_time', '>', $startTime);
                            });
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'capacity', 'price_per_day', 'salle_type']);

        return response()->json([
            'salles' => $salles,
        ]);
    }

    public function searchClients(Request $request)
    {
        $this->enforcePermission('reservations', 'create', 'create');

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
        ]);

        $keyword = trim($validated['q']);

        $clients = Client::query()
            ->where(function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('cin', 'like', "%{$keyword}%");

                if (Schema::hasColumn('clients', 'first_name')) {
                    $query->orWhere('first_name', 'like', "%{$keyword}%");
                }

                if (Schema::hasColumn('clients', 'phone_2')) {
                    $query->orWhere('phone_2', 'like', "%{$keyword}%");
                }
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        $formatted = $clients->map(function (Client $client) {
            $fullName = trim(($client->first_name ?? '') . ' ' . ($client->name ?? ''));
            $display = $fullName !== '' ? $fullName : ($client->name ?? 'Client');

            if (! empty($client->cin)) {
                $display .= ' - CIN: ' . $client->cin;
            }

            if (! empty($client->phone)) {
                $display .= ' - Tel: ' . $client->phone;
            }

            return [
                'id' => $client->id,
                'label' => $display,
            ];
        })->values();

        return response()->json([
            'clients' => $formatted,
        ]);
    }

    public function quickStoreClient(Request $request)
    {
        $this->enforcePermission('reservations', 'create', 'create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'cin' => ['nullable', 'string', 'max:80', 'unique:clients,cin'],
        ]);

        $payload = [
            'name' => $validated['name'],
            'status' => 'active',
        ];

        if (Schema::hasColumn('clients', 'first_name')) {
            $payload['first_name'] = $validated['first_name'] ?? null;
        }

        if (Schema::hasColumn('clients', 'phone')) {
            $payload['phone'] = $validated['phone'] ?? null;
        }

        if (Schema::hasColumn('clients', 'cin')) {
            $payload['cin'] = $validated['cin'] ?? null;
        }

        if (Schema::hasColumn('clients', 'client_type')) {
            $payload['client_type'] = 'personne-physique';
        }

        if (Schema::hasColumn('clients', 'source')) {
            $payload['source'] = 'passager';
        }

        $client = Client::query()->create($payload);

        $label = trim(($client->first_name ?? '') . ' ' . ($client->name ?? ''));

        return response()->json([
            'client' => [
                'id' => $client->id,
                'label' => $label !== '' ? $label : $client->name,
            ],
            'message' => 'Client ajoute avec succes.',
        ]);
    }
}
