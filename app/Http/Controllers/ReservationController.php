<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationAdditionalService;
use App\Models\Salle;
use App\Models\ServiceModuleItem;
use App\Models\ServiceModulePack;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReservationController extends MatrixAwareController
{
    private const ADDITIONAL_SERVICE_MODULES = [
        'troupe-musicale' => 'Troupe musicale',
        'photographe' => 'Photographe',
        'chanteur' => 'Chanteur',
        'notaire' => 'Notaire',
        'animation' => 'Animation',
        'voiture' => 'Voiture',
    ];

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

        $service = trim((string) request()->query('service', 'salles'));
        if ($service === '') {
            $service = 'salles';
        }

        if ($service !== 'all' && ! array_key_exists($service, self::RESERVATION_SERVICES)) {
            $service = 'salles';
        }

        $reservationsQuery = Reservation::query()->with(['client', 'salle']);
        $hasServiceSlugColumn = Schema::hasColumn('reservations', 'service_slug');

        if ($service !== 'all') {
            if ($hasServiceSlugColumn) {
                if ($service === 'salles') {
                    $reservationsQuery->where(function ($query) {
                        $query
                            ->where('service_slug', 'salles')
                            ->orWhereNull('service_slug');
                    });
                } else {
                    $reservationsQuery->where('service_slug', $service);
                }
            } elseif ($service !== 'salles') {
                $reservationsQuery->whereRaw('1 = 0');
            }
        }

        return view('reservations.index', [
            'title' => 'Reservations',
            'reservations' => $reservationsQuery->latest()->get(),
            'clients' => Client::query()->orderBy('name')->get(),
            'salles' => Salle::query()->orderBy('name')->get(),
            'governorates' => self::GOVERNORATES,
            'sources' => self::SOURCES,
            'reservationService' => $service,
            'reservationServiceLabel' => $service !== 'all' ? self::RESERVATION_SERVICES[$service] : 'Toutes',
        ]);
    }

    public function create()
    {
        return redirect()->route('reservations.index');
    }

    public function show(Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'list', 'view');

        $reservation->load(['client', 'salle', 'user', 'payments.user', 'additionalServices.item', 'additionalServices.pack', 'additionalServices.linkedReservation.payments']);

        $currentStart = $this->reservationDateTime($reservation->start_date, $reservation->start_time);
        $currentEnd = $this->reservationDateTime($reservation->end_date, $reservation->end_time);
        $nearbyCreneaux = collect();

        if ($reservation->salle_id && $currentStart && $currentEnd) {
            $candidateReservations = Reservation::query()
                ->with('client:id,name,first_name')
                ->where('salle_id', $reservation->salle_id)
                ->where('id', '!=', $reservation->id)
                ->where('status', '!=', 'cancelled')
                ->whereDate('start_date', '<=', $currentEnd->toDateString())
                ->whereDate('end_date', '>=', $currentStart->toDateString())
                ->get();

            $nearbyCreneaux = $candidateReservations
                ->map(function (Reservation $other) use ($currentStart, $currentEnd) {
                    $otherStart = $this->reservationDateTime($other->start_date, $other->start_time);
                    $otherEnd = $this->reservationDateTime($other->end_date, $other->end_time);

                    if (! $otherStart || ! $otherEnd) {
                        return null;
                    }

                    $isBefore = $otherEnd->lessThanOrEqualTo($currentStart)
                        && $otherEnd->diffInMinutes($currentStart) <= 90;

                    $isAfter = $otherStart->greaterThanOrEqualTo($currentEnd)
                        && $otherStart->diffInMinutes($currentEnd) <= 90;

                    if (! $isBefore && ! $isAfter) {
                        return null;
                    }

                    return [
                        'id' => $other->id,
                        'title' => $other->title ?: ('Reservation #' . $other->id),
                        'client' => trim((string) (($other->client?->first_name ?? '') . ' ' . ($other->client?->name ?? ''))),
                        'position' => $isBefore ? 'before' : 'after',
                        'gap_minutes' => $isBefore
                            ? $otherEnd->diffInMinutes($currentStart)
                            : $currentEnd->diffInMinutes($otherStart),
                        'start' => $otherStart->format('d/m/Y H:i'),
                        'end' => $otherEnd->format('d/m/Y H:i'),
                    ];
                })
                ->filter()
                ->sortBy('gap_minutes')
                ->values();
        }

        $serviceItems = ServiceModuleItem::query()
            ->whereIn('module_slug', array_keys(self::ADDITIONAL_SERVICE_MODULES))
            ->where('status', 'active')
            ->orderBy('module_slug')
            ->orderBy('name')
            ->get(['id', 'module_slug', 'name', 'base_price']);

        $servicePacks = ServiceModulePack::query()
            ->whereIn('module_slug', array_keys(self::ADDITIONAL_SERVICE_MODULES))
            ->where('status', 'active')
            ->orderBy('module_slug')
            ->orderBy('name')
            ->get(['id', 'module_slug', 'name', 'price']);

        $serviceOptionsByModule = [];
        foreach (self::ADDITIONAL_SERVICE_MODULES as $slug => $label) {
            $serviceOptionsByModule[$slug] = [
                'label' => $label,
                'options' => [],
            ];
        }

        foreach ($serviceItems as $item) {
            $serviceOptionsByModule[$item->module_slug]['options'][] = [
                'ref' => 'item:' . $item->id,
                'name' => $item->name,
                'amount' => (float) $item->base_price,
                'kind' => 'Item',
            ];
        }

        foreach ($servicePacks as $pack) {
            $serviceOptionsByModule[$pack->module_slug]['options'][] = [
                'ref' => 'pack:' . $pack->id,
                'name' => $pack->name,
                'amount' => (float) $pack->price,
                'kind' => 'Pack',
            ];
        }

        $additionalServicesByCategory = $reservation->additionalServices
            ->groupBy('module_slug')
            ->mapWithKeys(function ($rows, $slug) {
                $label = self::ADDITIONAL_SERVICE_MODULES[$slug] ?? $slug;

                return [
                    $slug => [
                        'label' => $label,
                        'rows' => $rows,
                    ],
                ];
            });

        return view('reservations.show', [
            'title' => 'Detail reservation',
            'reservation' => $reservation,
            'governorates' => self::GOVERNORATES,
            'sources' => self::SOURCES,
            'nearbyCreneaux' => $nearbyCreneaux,
            'serviceOptionsByModule' => $serviceOptionsByModule,
            'additionalServicesByCategory' => $additionalServicesByCategory,
            'additionalServiceModules' => self::ADDITIONAL_SERVICE_MODULES,
        ]);
    }

    public function storeAdditionalService(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        if (($reservation->service_slug ?? 'salles') !== 'salles') {
            return redirect()->route('reservations.show', $reservation)->withErrors([
                'service' => 'Les services supplementaires ne sont autorises que pour les reservations de type salle.',
            ]);
        }

        if (! Schema::hasColumn('reservations', 'service_slug')) {
            return redirect()->route('reservations.show', $reservation)->withErrors([
                'service' => 'Le systeme de categories de reservation n est pas disponible. Lance les migrations en attente.',
            ]);
        }

        $validated = $request->validate([
            'module_slug' => ['required', Rule::in(array_keys(self::ADDITIONAL_SERVICE_MODULES))],
            'service_ref' => ['required', 'string', 'max:80'],
            'service_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        $serviceRef = (string) $validated['service_ref'];
        $parts = explode(':', $serviceRef, 2);

        if (count($parts) !== 2 || ! in_array($parts[0], ['item', 'pack'], true) || ! ctype_digit($parts[1])) {
            return redirect()->route('reservations.show', $reservation)->withErrors([
                'service_ref' => 'Service selectionne invalide.',
            ])->withInput();
        }

        [$kind, $idRaw] = $parts;
        $serviceId = (int) $idRaw;

        $itemId = null;
        $packId = null;
        $label = '';
        $defaultAmount = 0.0;

        if ($kind === 'item') {
            $item = ServiceModuleItem::query()
                ->where('id', $serviceId)
                ->where('module_slug', $validated['module_slug'])
                ->where('status', 'active')
                ->first();

            if (! $item) {
                return redirect()->route('reservations.show', $reservation)->withErrors([
                    'service_ref' => 'Item de service introuvable ou inactif.',
                ])->withInput();
            }

            $itemId = $item->id;
            $label = $item->name;
            $defaultAmount = (float) $item->base_price;
        } else {
            $pack = ServiceModulePack::query()
                ->where('id', $serviceId)
                ->where('module_slug', $validated['module_slug'])
                ->where('status', 'active')
                ->first();

            if (! $pack) {
                return redirect()->route('reservations.show', $reservation)->withErrors([
                    'service_ref' => 'Pack de service introuvable ou inactif.',
                ])->withInput();
            }

            $packId = $pack->id;
            $label = $pack->name;
            $defaultAmount = (float) $pack->price;
        }

        $serviceAmount = isset($validated['service_amount']) ? (float) $validated['service_amount'] : $defaultAmount;

        DB::transaction(function () use ($reservation, $validated, $itemId, $packId, $label, $serviceAmount): void {
            $linkedTitle = trim(($reservation->title ?: ('Reservation #' . $reservation->id)) . ' - ' . $label);
            if ($linkedTitle === '') {
                $linkedTitle = 'Service supplementaire';
            }

            $linkedReservation = Reservation::query()->create([
                'client_id' => $reservation->client_id,
                'salle_id' => $reservation->salle_id,
                'service_slug' => $validated['module_slug'],
                'user_id' => Auth::id(),
                'title' => mb_substr($linkedTitle, 0, 255),
                'start_date' => $reservation->start_date,
                'end_date' => $reservation->end_date,
                'start_time' => $reservation->start_time,
                'end_time' => $reservation->end_time,
                'status' => 'pending',
                'total_amount' => $serviceAmount,
                'note_admin' => 'Reservation supplementaire liee a la reservation salle #' . $reservation->id,
            ]);

            ReservationAdditionalService::query()->create([
                'reservation_id' => $reservation->id,
                'linked_reservation_id' => $linkedReservation->id,
                'module_slug' => $validated['module_slug'],
                'service_module_item_id' => $itemId,
                'service_module_pack_id' => $packId,
                'label' => $label,
                'amount' => $serviceAmount,
                'note' => $validated['note'] ?? null,
            ]);
        });

        return redirect()->route('reservations.show', $reservation)->with('success', 'Service supplementaire ajoute avec reservation liee.');
    }

    public function destroyAdditionalService(Reservation $reservation, ReservationAdditionalService $additionalService)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        abort_if((int) $additionalService->reservation_id !== (int) $reservation->id, 404);

        $additionalService->load('linkedReservation.payments');

        DB::transaction(function () use ($additionalService): void {
            $linkedReservation = $additionalService->linkedReservation;

            if ($linkedReservation) {
                $hasPayments = $linkedReservation->payments->isNotEmpty();

                if ($hasPayments) {
                    $currentNote = trim((string) ($linkedReservation->note_admin ?? ''));
                    $append = 'Reservation supplementaire retiree depuis la reservation parent.';
                    $linkedReservation->update([
                        'status' => 'cancelled',
                        'note_admin' => $currentNote !== '' ? ($currentNote . ' | ' . $append) : $append,
                    ]);
                } else {
                    $linkedReservation->delete();
                }
            }

            $additionalService->delete();
        });

        return redirect()->route('reservations.show', $reservation)->with('success', 'Service supplementaire retire.');
    }

    public function updateAdditionalServiceStartTime(Request $request, Reservation $reservation, ReservationAdditionalService $additionalService)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        abort_if((int) $additionalService->reservation_id !== (int) $reservation->id, 404);

        $request->validate([
            'start_time' => ['required', 'date_format:H:i'],
        ]);

        $linkedReservation = $additionalService->linkedReservation;
        if ($linkedReservation) {
            $linkedReservation->update(['start_time' => $request->start_time . ':00']);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('reservations.show', $reservation)->with('success', 'Heure de debut mise a jour.');
    }

    public function updateClient(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        $client = $reservation->client;
        if (! $client) {
            return redirect()->route('reservations.show', $reservation)->withErrors([
                'client' => 'Client lie a cette reservation introuvable.',
            ]);
        }

        $hasExtendedColumns = Schema::hasColumn('clients', 'client_type')
            && Schema::hasColumn('clients', 'first_name')
            && Schema::hasColumn('clients', 'governorate');

        if (! $hasExtendedColumns) {
            $basicValidated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email'],
                'phone' => ['required', 'string', 'max:50'],
                'date_cin' => ['nullable', 'date'],
                'city' => ['nullable', 'string', 'max:255'],
            ]);

            $client->update($basicValidated);

            return redirect()->route('reservations.show', $reservation)->with('success', 'Donnees client mises a jour.');
        }

        $validated = $request->validate([
            'client_type' => ['required', Rule::in(['personne-physique', 'societe'])],
            'fiscal_number' => ['nullable', 'string', 'max:100', 'required_if:client_type,societe'],
            'company_name' => ['nullable', 'string', 'max:255', 'required_if:client_type,societe'],
            'first_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
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

        if (($validated['client_type'] ?? null) === 'personne-physique') {
            $validated['fiscal_number'] = null;
            $validated['company_name'] = null;
        }

        $client->update($validated);

        return redirect()->route('reservations.show', $reservation)->with('success', 'Donnees client mises a jour.');
    }

    public function availableSallesForReservation(Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        $salles = Salle::query()
            ->where('status', 'active')
            ->whereDoesntHave('reservations', function ($query) use ($reservation) {
                $query
                    ->where('id', '!=', $reservation->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('start_date', '<=', $reservation->end_date)
                    ->whereDate('end_date', '>=', $reservation->start_date)
                    ->where(function ($timeQuery) use ($reservation) {
                        $timeQuery
                            ->whereNull('start_time')
                            ->orWhereNull('end_time')
                            ->orWhere(function ($overlapQuery) use ($reservation) {
                                $overlapQuery
                                    ->where('start_time', '<', $reservation->end_time)
                                    ->where('end_time', '>', $reservation->start_time);
                            });
                    });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'capacity', 'price_per_day', 'salle_type', 'color_code']);

        return response()->json([
            'salles' => $salles,
        ]);
    }

    public function updateSalle(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        $validated = $request->validate([
            'salle_id' => ['required', 'exists:salles,id'],
        ]);

        $salleId = (int) $validated['salle_id'];

        if ($reservation->salle_id !== $salleId) {
            $hasConflict = Reservation::query()
                ->where('id', '!=', $reservation->id)
                ->where('salle_id', $salleId)
                ->where('status', '!=', 'cancelled')
                ->whereDate('start_date', '<=', $reservation->end_date)
                ->whereDate('end_date', '>=', $reservation->start_date)
                ->where(function ($timeQuery) use ($reservation) {
                    $timeQuery
                        ->whereNull('start_time')
                        ->orWhereNull('end_time')
                        ->orWhere(function ($overlapQuery) use ($reservation) {
                            $overlapQuery
                                ->where('start_time', '<', $reservation->end_time)
                                ->where('end_time', '>', $reservation->start_time);
                        });
                })
                ->exists();

            if ($hasConflict) {
                return redirect()
                    ->route('reservations.show', $reservation)
                    ->withErrors(['salle_id' => 'Cette salle n est pas disponible sur ce creneau.'])
                    ->withInput();
            }
        }

        $reservation->update([
            'salle_id' => $salleId,
        ]);

        return redirect()->route('reservations.show', $reservation)->with('success', 'Salle de la reservation mise a jour.');
    }

    public function storePayment(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('payments', 'create', 'create');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'method' => ['required', 'string', 'max:50'],
            'phase' => ['required', Rule::in(['avance', 'partie-1', 'partie-2', 'partie-3', 'reste'])],
            'note' => ['nullable', 'string'],
        ]);

        $totalAmount = (float) ($reservation->total_amount ?? 0);
        if ($totalAmount <= 0) {
            return redirect()
                ->route('reservations.show', $reservation)
                ->withErrors(['amount' => 'Definis d abord un montant total sur la reservation.'])
                ->withInput();
        }

        $alreadyPaid = (float) $reservation->payments()->sum('amount');
        $remaining = max($totalAmount - $alreadyPaid, 0);
        $amount = (float) $validated['amount'];
        $paymentCount = (int) $reservation->payments()->count();

        if ($remaining <= 0) {
            return redirect()
                ->route('reservations.show', $reservation)
                ->withErrors(['amount' => 'Cette reservation est deja soldee.'])
                ->withInput();
        }

        if ($amount > $remaining) {
            return redirect()
                ->route('reservations.show', $reservation)
                ->withErrors(['amount' => 'Le montant depasse le reste a payer.'])
                ->withInput();
        }

        if ($paymentCount === 0 && $validated['phase'] !== 'avance') {
            return redirect()
                ->route('reservations.show', $reservation)
                ->withErrors(['phase' => 'Le premier paiement doit etre une avance.'])
                ->withInput();
        }

        if ($validated['phase'] === 'reste' && abs($amount - $remaining) > 0.009) {
            return redirect()
                ->route('reservations.show', $reservation)
                ->withErrors(['amount' => 'Pour la phase "reste", le montant doit couvrir exactement le solde.'])
                ->withInput();
        }

        $phaseLabel = match ($validated['phase']) {
            'avance' => 'Avance',
            'partie-1' => 'Partie 1',
            'partie-2' => 'Partie 2',
            'partie-3' => 'Partie 3',
            default => 'Reste',
        };

        Payment::query()->create([
            'reservation_id' => $reservation->id,
            'user_id' => Auth::id(),
            'amount' => $amount,
            'method' => $validated['method'],
            'reference' => $phaseLabel,
            'status' => 'paid',
            'paid_at' => now()->format('Y-m-d H:i:s'),
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('reservations.show', $reservation)->with('success', 'Paiement ajoute a la reservation.');
    }

    public function store(Request $request)
    {
        $this->enforcePermission('reservations', 'create', 'create');

        $resolvedClientId = $this->resolveReservationClient($request);

        $validated = $request->validate([
            'salle_id' => ['required', 'exists:salles,id'],
            'service_slug' => ['nullable', Rule::in(array_keys(self::RESERVATION_SERVICES))],
            'title' => ['required', 'string', 'max:255'],
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

        if (Schema::hasColumn('reservations', 'service_slug')) {
            $validated['service_slug'] = $validated['service_slug'] ?? 'salles';
        } else {
            unset($validated['service_slug']);
        }

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
            'service_slug' => ['nullable', Rule::in(array_keys(self::RESERVATION_SERVICES))],
            'title' => ['required', 'string', 'max:255'],
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

        if (Schema::hasColumn('reservations', 'service_slug')) {
            $validated['service_slug'] = $validated['service_slug'] ?? ($reservation->service_slug ?: 'salles');
        } else {
            unset($validated['service_slug']);
        }

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

    private function reservationDateTime(?string $dateValue, ?string $timeValue): ?Carbon
    {
        if (empty($dateValue) || empty($timeValue)) {
            return null;
        }

        try {
            return Carbon::parse($dateValue . ' ' . $timeValue);
        } catch (\Throwable) {
            return null;
        }
    }
}
