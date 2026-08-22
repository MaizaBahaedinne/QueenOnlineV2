<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Reservation;
use App\Models\Salle;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReservationController extends MatrixAwareController
{
    private const RESERVATION_SERVICES = [
        'salles' => 'Salles',
        'troupe-musicale' => 'Troupe musicale',
        'photographe' => 'Photographe',
        'chanteur' => 'Chanteur',
        'notaire' => 'Notaire',
        'animation' => 'Animation',
        'voiture' => 'Voiture',
    ];

    private const GOVERNORATES = [
        'Ariana', 'Beja', 'Ben Arous', 'Bizerte', 'Gabes', 'Gafsa', 'Jendouba', 'Kairouan',
        'Kasserine', 'Kebili', 'Le Kef', 'Mahdia', 'La Manouba', 'Medenine', 'Monastir',
        'Nabeul', 'Sfax', 'Sidi Bouzid', 'Siliana', 'Sousse', 'Tataouine', 'Tozeur', 'Tunis', 'Zaghouan',
    ];

    private const SOURCES = [
        'passager',
        'reseaux-sociaux-web',
        'presence-event',
        'recommandation',
        'connaissance-queenpark',
    ];

    public function index()
    {
        $this->enforcePermission('reservations', 'list', 'view');

        $service = trim((string) request()->query('service', ''));
        if ($service !== '' && ! array_key_exists($service, self::RESERVATION_SERVICES)) {
            $service = '';
        }

        return view('reservations.index', [
            'title' => 'Reservations',
            'reservations' => Reservation::query()->with(['client', 'salle'])->latest()->get(),
            'clients' => Client::query()->orderBy('name')->get(),
            'salles' => Salle::query()->orderBy('name')->get(),
            'governorates' => self::GOVERNORATES,
            'sources' => self::SOURCES,
            'reservationService' => $service,
            'reservationServiceLabel' => $service !== '' ? self::RESERVATION_SERVICES[$service] : null,
        ]);
    }

    public function create()
    {
        return redirect()->route('reservations.index');
    }

    public function store(Request $request)
    {
        $this->enforcePermission('reservations', 'create', 'create');

        $resolvedClientId = $this->resolveReservationClient($request);

        $validated = $request->validate([
            'salle_id' => ['required', 'exists:salles,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', 'after_or_equal:08:00', 'before_or_equal:23:59'],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
                'before_or_equal:23:59',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $start = Carbon::createFromFormat('H:i', (string) $request->input('start_time'));
                    $end = Carbon::createFromFormat('H:i', (string) $value);

                    if ($start && $end && $start->diffInMinutes($end, false) < 60) {
                        $fail('Heure fin doit etre au moins heure debut + 1 heure.');
                    }
                },
            ],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['client_id'] = $resolvedClientId;

        Reservation::create($validated);

        return redirect()->route('reservations.index')->with('success', 'Reservation creee.');
    }

    public function update(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'salle_id' => ['required', 'exists:salles,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', 'after_or_equal:08:00', 'before_or_equal:23:59'],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
                'before_or_equal:23:59',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $start = Carbon::createFromFormat('H:i', (string) $request->input('start_time'));
                    $end = Carbon::createFromFormat('H:i', (string) $value);

                    if ($start && $end && $start->diffInMinutes($end, false) < 60) {
                        $fail('Heure fin doit etre au moins heure debut + 1 heure.');
                    }
                },
            ],
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

        $validator = Validator::make($request->all(), [
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', 'after_or_equal:08:00', 'before_or_equal:23:59'],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
                'before_or_equal:23:59',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $start = Carbon::createFromFormat('H:i', (string) $request->input('start_time'));
                    $end = Carbon::createFromFormat('H:i', (string) $value);

                    if ($start && $end && $start->diffInMinutes($end, false) < 60) {
                        $fail('Heure fin doit etre au moins heure debut + 1 heure.');
                    }
                },
            ],
            'exclude_reservation_id' => ['nullable', 'integer', 'exists:reservations,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Parametres invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

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

        $validator = Validator::make($request->all(), [
            'cin' => ['required', 'regex:/^[0-9]{8}$/'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Parametres invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $clients = Client::query()
            ->where('cin', $validator->validated()['cin'])
            ->limit(1)
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
                'data' => [
                    'client_type' => $client->client_type ?? 'personne-physique',
                    'fiscal_number' => $client->fiscal_number,
                    'company_name' => $client->company_name,
                    'first_name' => $client->first_name,
                    'name' => $client->name,
                    'cin' => $client->cin,
                    'date_cin' => $client->date_cin,
                    'email' => $client->email,
                    'address_number' => $client->address_number,
                    'address_street' => $client->address_street,
                    'city' => $client->city,
                    'governorate' => $client->governorate,
                    'phone' => $client->phone,
                    'phone_label_1' => $client->phone_label_1,
                    'phone_2' => $client->phone_2,
                    'phone_label_2' => $client->phone_label_2,
                    'source' => $client->source,
                    'note' => $client->note,
                ],
            ];
        })->values();

        return response()->json([
            'clients' => $formatted,
        ]);
    }

    public function quickStoreClient(Request $request)
    {
        $this->enforcePermission('reservations', 'create', 'create');

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'cin' => ['nullable', 'string', 'max:80', 'unique:clients,cin'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Parametres invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

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

    private function resolveReservationClient(Request $request): int
    {
        $clientIdInput = $request->input('client_id');
        $existingClient = null;

        if (! empty($clientIdInput)) {
            $existingClient = Client::query()->find($clientIdInput);
            if (! $existingClient) {
                throw ValidationException::withMessages([
                    'client_id' => ['Client selectionne introuvable.'],
                ]);
            }
        }

        $hasExtendedColumns = Schema::hasColumn('clients', 'client_type')
            && Schema::hasColumn('clients', 'first_name')
            && Schema::hasColumn('clients', 'governorate');

        if (! $hasExtendedColumns) {
            $basicRules = [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email'],
                'phone' => ['required', 'string', 'max:50'],
                'cin' => ['required', 'regex:/^[0-9]{8}$/', Rule::unique('clients', 'cin')->ignore($existingClient?->id)],
                'date_cin' => ['nullable', 'date'],
                'city' => ['nullable', 'string', 'max:255'],
            ];

            $basicValidated = $request->validate($basicRules);
            if ($existingClient) {
                $existingClient->update($basicValidated);
                return $existingClient->id;
            }

            $basicValidated['status'] = 'active';
            $createdClient = Client::query()->create($basicValidated);
            return $createdClient->id;
        }

        $extendedValidated = $request->validate([
            'client_type' => ['required', Rule::in(['personne-physique', 'societe'])],
            'fiscal_number' => ['nullable', 'string', 'max:100', 'required_if:client_type,societe'],
            'company_name' => ['nullable', 'string', 'max:255', 'required_if:client_type,societe'],
            'first_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'cin' => ['required', 'regex:/^[0-9]{8}$/', Rule::unique('clients', 'cin')->ignore($existingClient?->id)],
            'date_cin' => ['nullable', 'date'],
            'email' => ['nullable', 'email'],
            'address_number' => ['nullable', 'string', 'max:50'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'governorate' => ['required', Rule::in(self::GOVERNORATES)],
            'phone' => ['required', 'string', 'max:50'],
            'phone_label_1' => ['nullable', 'string', 'max:100'],
            'phone_2' => ['nullable', 'string', 'max:50'],
            'phone_label_2' => ['nullable', 'string', 'max:100'],
            'source' => ['required', Rule::in(self::SOURCES)],
            'note' => ['nullable', 'string'],
        ]);

        if (($extendedValidated['client_type'] ?? null) === 'personne-physique') {
            $extendedValidated['fiscal_number'] = null;
            $extendedValidated['company_name'] = null;
        }

        if ($existingClient) {
            $existingClient->update($extendedValidated);
            return $existingClient->id;
        }

        $extendedValidated['status'] = 'active';
        $createdClient = Client::query()->create($extendedValidated);
        return $createdClient->id;
    }
}
