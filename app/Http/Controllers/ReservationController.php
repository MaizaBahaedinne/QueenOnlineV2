<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientCreditLedger;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationAdditionalService;
use App\Models\ReservationSalleOption;
use App\Models\ReservationCancellation;
use App\Models\Salle;
use App\Models\SalleOption;
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
        'connaissance-queenpark' => 'Par des amis ou connaissance de l equipe de Queen Park',
        'reseaux-sociaux-web' => 'Reseaux sociaux (Facebook, Instagram, tiktok ...)',
        'publicite-classique' => 'Publicite (TV, radio, affiches...)',
        'recherche-internet' => 'Recherche sur Internet',
        'presence-event' => 'Lors d un evenement',
        'passager' => 'Passager',
        'recommandation' => 'Recomandation d une connaisance',
    ];

    private const EVENT_TYPES = [
        'Marriage',
        'Finacailles',
        'Hena',
        'Outya',
        'Congret',
        'Circoncision',
        'Team Building',
        'Anniversaire',
        'Evenement',
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

        $scope = trim((string) request()->query('scope', 'all'));
        if (! in_array($scope, ['all', 'interne', 'externe'], true)) {
            $scope = 'all';
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

        if ($scope === 'interne') {
            if ($hasServiceSlugColumn) {
                $reservationsQuery->where(function ($query) {
                    $query
                        ->where('service_slug', 'salles')
                        ->orWhereNull('service_slug');
                });
            }
        } elseif ($scope === 'externe') {
            if ($hasServiceSlugColumn) {
                $reservationsQuery
                    ->whereNotNull('service_slug')
                    ->where('service_slug', '!=', 'salles');
            } else {
                $reservationsQuery->whereRaw('1 = 0');
            }
        }

        $scopeCountsQuery = Reservation::query();
        if ($service !== 'all') {
            if ($hasServiceSlugColumn) {
                if ($service === 'salles') {
                    $scopeCountsQuery->where(function ($query) {
                        $query
                            ->where('service_slug', 'salles')
                            ->orWhereNull('service_slug');
                    });
                } else {
                    $scopeCountsQuery->where('service_slug', $service);
                }
            } elseif ($service !== 'salles') {
                $scopeCountsQuery->whereRaw('1 = 0');
            }
        }

        $internalCount = $hasServiceSlugColumn
            ? (clone $scopeCountsQuery)
                ->where(function ($query) {
                    $query
                        ->where('service_slug', 'salles')
                        ->orWhereNull('service_slug');
                })
                ->count()
            : (clone $scopeCountsQuery)->count();

        $externalCount = $hasServiceSlugColumn
            ? (clone $scopeCountsQuery)
                ->whereNotNull('service_slug')
                ->where('service_slug', '!=', 'salles')
                ->count()
            : 0;

        $scopeLabel = match ($scope) {
            'interne' => 'Interne',
            'externe' => 'Externe',
            default => 'Toutes',
        };

        $indexServiceItems = ServiceModuleItem::query()
            ->whereIn('module_slug', ['photographe', 'troupe-musicale', 'chanteur', 'notaire', 'animation'])
            ->where('status', 'active')
            ->orderBy('module_slug')
            ->orderBy('name')
            ->get(['id', 'module_slug', 'name', 'base_price']);

        $indexServicePacks = ServiceModulePack::query()
            ->whereIn('module_slug', ['photographe', 'troupe-musicale'])
            ->where('status', 'active')
            ->orderBy('module_slug')
            ->orderBy('name')
            ->get(['id', 'module_slug', 'name', 'price']);

        $indexProvidersByModule = [
            'photographe' => [],
            'troupe-musicale' => [],
            'chanteur' => [],
            'notaire' => [],
            'animation' => [],
        ];

        foreach ($indexServiceItems as $item) {
            $indexProvidersByModule[$item->module_slug][] = [
                'id' => (int) $item->id,
                'name' => $item->name,
                'base_price' => (float) $item->base_price,
            ];
        }

        $indexPacksByModule = [
            'photographe' => [],
            'troupe-musicale' => [],
        ];

        foreach ($indexServicePacks as $pack) {
            $indexPacksByModule[$pack->module_slug][] = [
                'id' => (int) $pack->id,
                'name' => $pack->name,
                'price' => (float) $pack->price,
            ];
        }

        return view('reservations.index', [
            'title' => 'Reservations',
            'reservations' => $reservationsQuery->latest()->get(),
            'clients' => Client::query()->orderBy('name')->get(),
            'salles' => Salle::query()->orderBy('name')->get(),
            'governorates' => self::GOVERNORATES,
            'sources' => self::SOURCES,
            'eventTypes' => self::EVENT_TYPES,
            'reservationService' => $service,
            'reservationServiceLabel' => $service !== 'all' ? self::RESERVATION_SERVICES[$service] : 'Toutes',
            'reservationScope' => $scope,
            'reservationScopeLabel' => $scopeLabel,
            'reservationScopeInternalCount' => $internalCount,
            'reservationScopeExternalCount' => $externalCount,
            'serviceProvidersByModule' => $indexProvidersByModule,
            'servicePacksByModule' => $indexPacksByModule,
        ]);
    }

    public function create()
    {
        return redirect()->route('reservations.index');
    }

    public function show(Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'list', 'view');

        $reservation->load(['client', 'salle', 'user', 'payments.user', 'additionalServices.item', 'additionalServices.pack', 'additionalServices.linkedReservation.payments', 'salleOptionRows.option']);

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

        $availableSalleOptions = collect();
        if (($reservation->service_slug ?? 'salles') === 'salles' && $reservation->salle_id) {
            $availableSalleOptions = SalleOption::query()
                ->where('salle_id', $reservation->salle_id)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'price']);
        }

        $reservationServiceSlug = $this->reservationServiceSlug($reservation);
        $clientCreditBalance = $this->getClientCreditBalance((int) $reservation->client_id, $reservationServiceSlug);
        return view('reservations.show', [
            'title' => 'Detail reservation',
            'reservation' => $reservation,
            'salles' => Salle::query()->orderBy('name')->get(['id', 'name', 'capacity']),
            'governorates' => self::GOVERNORATES,
            'sources' => self::SOURCES,
            'eventTypes' => self::EVENT_TYPES,
            'nearbyCreneaux' => $nearbyCreneaux,
            'serviceOptionsByModule' => $serviceOptionsByModule,
            'additionalServicesByCategory' => $additionalServicesByCategory,
            'availableSalleOptions' => $availableSalleOptions,
            'additionalServiceModules' => self::ADDITIONAL_SERVICE_MODULES,
            'clientCreditBalance' => $clientCreditBalance,
            'creditServiceLabel' => self::RESERVATION_SERVICES[$reservationServiceSlug] ?? 'Service',
            'reservationScopeLabel' => ($reservation->service_slug ?? 'salles') === 'salles' ? 'Interne' : 'Externe',
        ]);
    }

    public function storeSalleOption(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        if (($reservation->service_slug ?? 'salles') !== 'salles' || ! $reservation->salle_id) {
            return redirect()->route('reservations.show', $reservation)->withErrors([
                'salle_option' => 'Les options salle sont reservees aux reservations de type salle.',
            ]);
        }

        $validated = $request->validate([
            'salle_option_id' => ['required', 'integer', 'exists:salle_options,id'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ]);

        $option = SalleOption::query()
            ->where('id', (int) $validated['salle_option_id'])
            ->where('salle_id', $reservation->salle_id)
            ->where('status', 'active')
            ->first();

        if (! $option) {
            return redirect()->route('reservations.show', $reservation)->withErrors([
                'salle_option_id' => 'Option invalide pour cette salle.',
            ])->withInput();
        }

        $amount = array_key_exists('amount', $validated) && $validated['amount'] !== null && $validated['amount'] !== ''
            ? (float) $validated['amount']
            : (float) $option->price;

        ReservationSalleOption::query()->create([
            'reservation_id' => $reservation->id,
            'salle_option_id' => $option->id,
            'label' => $option->name,
            'amount' => $amount,
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()->route('reservations.show', $reservation)->with('success', 'Option salle ajoutee a la reservation.');
    }

    public function destroySalleOption(Reservation $reservation, ReservationSalleOption $salleOptionRow)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        abort_if((int) $salleOptionRow->reservation_id !== (int) $reservation->id, 404);

        $salleOptionRow->delete();

        return redirect()->route('reservations.show', $reservation)->with('success', 'Option salle retiree de la reservation.');
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

            $dueDate = null;
            if (! empty($reservation->start_date)) {
                $dueDate = Carbon::parse((string) $reservation->start_date)->subDays(30)->toDateString();
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
                'payment_due_date' => $dueDate,
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
            'source' => ['required', Rule::in(array_keys(self::SOURCES))],
            'note' => ['nullable', 'string'],
        ]);

        if (($validated['client_type'] ?? null) === 'personne-physique') {
            $validated['fiscal_number'] = null;
            $validated['company_name'] = null;
        }

        $client->update($validated);

        return redirect()->route('reservations.show', $reservation)->with('success', 'Donnees client mises a jour.');
    }

    public function availableSallesForReservation(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        $request->merge([
            'start_time' => substr((string) $request->input('start_time', ''), 0, 5),
            'end_time' => substr((string) $request->input('end_time', ''), 0, 5),
        ]);

        $validator = Validator::make($request->all(), [
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', 'after_or_equal:08:00', 'before_or_equal:23:59'],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
                'before_or_equal:23:59',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $startRaw = substr((string) $request->input('start_time'), 0, 5);
                    $endRaw = substr((string) $value, 0, 5);

                    $start = Carbon::createFromFormat('H:i', $startRaw);
                    $end = Carbon::createFromFormat('H:i', $endRaw);

                    if ($start && $end && $start->diffInMinutes($end, false) < 60) {
                        $fail('Heure fin doit etre au moins heure debut + 1 heure.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Parametres invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $eventDate = (string) $validated['event_date'];
        $startTime = (string) $validated['start_time'];
        $endTime = (string) $validated['end_time'];

        $salles = Salle::query()
            ->where('status', 'active')
            ->whereDoesntHave('reservations', function ($query) use ($reservation, $eventDate, $startTime, $endTime) {
                $query
                    ->where('id', '!=', $reservation->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('start_date', '<=', $eventDate)
                    ->whereDate('end_date', '>=', $eventDate)
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
            'method' => ['required', Rule::in(['cash', 'virement', 'carte', 'cheque', 'avoir'])],
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

        if ($validated['method'] === 'avoir') {
            if (! Schema::hasTable('client_credit_ledgers')) {
                return redirect()
                    ->route('reservations.show', $reservation)
                    ->withErrors(['method' => 'Module solde client indisponible. Lance les migrations.'])
                    ->withInput();
            }

            $serviceSlug = $this->reservationServiceSlug($reservation);
            $clientBalance = $this->getClientCreditBalance((int) $reservation->client_id, $serviceSlug);
            if ($amount > $clientBalance) {
                return redirect()
                    ->route('reservations.show', $reservation)
                    ->withErrors(['amount' => 'Le montant depasse le solde disponible du client pour ce type de service.'])
                    ->withInput();
            }

            $ledgerPayload = [
                'client_id' => $reservation->client_id,
                'reservation_id' => $reservation->id,
                'user_id' => Auth::id(),
                'type' => 'debit',
                'amount' => $amount,
                'description' => 'Utilisation avoir via paiement reservation #' . $reservation->id,
            ];

            if (Schema::hasColumn('client_credit_ledgers', 'service_slug')) {
                $ledgerPayload['service_slug'] = $serviceSlug;
            }

            ClientCreditLedger::query()->create($ledgerPayload);
        }

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

        $newTotalPaid = (float) $reservation->payments()->sum('amount');
        $newRemaining = max($totalAmount - $newTotalPaid, 0);

        if ($newRemaining <= 0.009 && ! in_array((string) $reservation->status, ['cancelled', 'completed'], true)) {
            $reservation->update([
                'status' => 'confirmed',
            ]);
        }

        return redirect()->route('reservations.show', $reservation)->with('success', 'Paiement ajoute a la reservation.');
    }

    public function cancel(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        if (! Schema::hasTable('client_credit_ledgers') || ! Schema::hasTable('reservation_cancellations')) {
            return redirect()->route('reservations.show', $reservation)->withErrors([
                'cancel' => 'Le workflow d annulation avec avoir n est pas encore initialise. Lance les migrations.',
            ]);
        }

        $validated = $request->validate([
            'present_on_site' => ['required', 'accepted'],
            'termination_signed' => ['required', 'accepted'],
            'note' => ['nullable', 'string'],
            'cancel_linked_reservation_ids' => ['nullable', 'array'],
            'cancel_linked_reservation_ids.*' => ['integer', 'exists:reservations,id'],
        ]);

        if ((string) $reservation->status === 'cancelled') {
            return redirect()->route('reservations.show', $reservation)->with('success', 'La reservation est deja annulee.');
        }

        $paidAmount = (float) $reservation->payments()->sum('amount');

        $reservation->loadMissing(['additionalServices.linkedReservation']);
        $allowedLinkedRows = $reservation->additionalServices
            ->filter(fn (ReservationAdditionalService $row) => (int) ($row->linked_reservation_id ?? 0) > 0)
            ->keyBy(fn (ReservationAdditionalService $row) => (int) $row->linked_reservation_id);

        $selectedLinkedReservationIds = collect($validated['cancel_linked_reservation_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        $forbiddenSelection = $selectedLinkedReservationIds
            ->first(fn (int $id) => ! $allowedLinkedRows->has($id));

        if ($forbiddenSelection !== null) {
            return redirect()->route('reservations.show', $reservation)->withErrors([
                'cancel_linked_reservation_ids' => 'Selection de reservation liee invalide.',
            ])->withInput();
        }

        $cancelledLinkedCount = 0;

        DB::transaction(function () use ($reservation, $validated, $paidAmount, $allowedLinkedRows, $selectedLinkedReservationIds, &$cancelledLinkedCount): void {
            $reservation->update([
                'status' => 'cancelled',
            ]);

            ReservationCancellation::query()->updateOrCreate(
                ['reservation_id' => $reservation->id],
                [
                    'client_id' => $reservation->client_id,
                    'user_id' => Auth::id(),
                    'present_on_site' => true,
                    'termination_signed' => true,
                    'credit_amount' => $paidAmount,
                    'note' => $validated['note'] ?? null,
                ]
            );

            if ($paidAmount > 0) {
                $ledgerPayload = [
                    'client_id' => $reservation->client_id,
                    'reservation_id' => $reservation->id,
                    'user_id' => Auth::id(),
                    'type' => 'credit',
                    'amount' => $paidAmount,
                    'description' => 'Avoir suite annulation reservation #' . $reservation->id,
                ];

                if (Schema::hasColumn('client_credit_ledgers', 'service_slug')) {
                    $ledgerPayload['service_slug'] = $this->reservationServiceSlug($reservation);
                }

                ClientCreditLedger::query()->create($ledgerPayload);
            }

            foreach ($selectedLinkedReservationIds as $linkedReservationId) {
                /** @var ReservationAdditionalService|null $linkedRow */
                $linkedRow = $allowedLinkedRows->get($linkedReservationId);
                $linkedReservation = $linkedRow?->linkedReservation;

                if (! $linkedReservation || (string) $linkedReservation->status === 'cancelled') {
                    continue;
                }

                $currentNote = trim((string) ($linkedReservation->note_admin ?? ''));
                $appendNote = 'Annulee depuis la reservation parent #' . $reservation->id;

                $linkedReservation->update([
                    'status' => 'cancelled',
                    'note_admin' => $currentNote !== '' ? ($currentNote . ' | ' . $appendNote) : $appendNote,
                ]);

                $cancelledLinkedCount++;
            }
        });

        $successMessage = 'Reservation annulee. Contrat de resiliation enregistre et avoir client cree.';
        if ($cancelledLinkedCount > 0) {
            $successMessage .= ' ' . $cancelledLinkedCount . ' reservation(s) de service supplementaire ont aussi ete annulees.';
        }

        return redirect()->route('reservations.show', $reservation)->with('success', $successMessage);
    }

    public function cloneReservation(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'create', 'create');

        $request->merge([
            'clone_start_time' => substr((string) $request->input('clone_start_time', ''), 0, 5),
            'clone_end_time' => substr((string) $request->input('clone_end_time', ''), 0, 5),
        ]);

        $validated = $request->validate([
            'clone_salle_id' => ['required', 'exists:salles,id'],
            'clone_title' => ['required', 'string', 'max:255'],
            'clone_guest_count' => ['nullable', 'integer', 'min:1'],
            'clone_event_type' => ['required', Rule::in(self::EVENT_TYPES)],
            'clone_start_date' => ['required', 'date', 'after_or_equal:today'],
            'clone_end_date' => ['required', 'date', 'after_or_equal:clone_start_date', 'after_or_equal:today'],
            'clone_start_time' => ['required', 'date_format:H:i', 'after_or_equal:08:00', 'before_or_equal:23:59'],
            'clone_end_time' => [
                'required',
                'date_format:H:i',
                'after:clone_start_time',
                'before_or_equal:23:59',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $startRaw = substr((string) $request->input('clone_start_time'), 0, 5);
                    $endRaw = substr((string) $value, 0, 5);

                    $start = Carbon::createFromFormat('H:i', $startRaw);
                    $end = Carbon::createFromFormat('H:i', $endRaw);

                    if ($start && $end && $start->diffInMinutes($end, false) < 60) {
                        $fail('Heure fin doit etre au moins heure debut + 1 heure.');
                    }
                },
            ],
            'clone_total_amount' => ['nullable', 'numeric', 'min:0'],
            'clone_note_admin' => ['nullable', 'string'],
            'copy_additional_services' => ['nullable', 'in:1'],
            'copy_salle_options' => ['nullable', 'in:1'],
        ]);

        $targetSalleId = (int) $validated['clone_salle_id'];
        $targetStartDate = (string) $validated['clone_start_date'];
        $targetEndDate = (string) $validated['clone_end_date'];
        $targetStartTime = (string) $validated['clone_start_time'];
        $targetEndTime = (string) $validated['clone_end_time'];

        $hasConflict = Reservation::query()
            ->where('salle_id', $targetSalleId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('start_date', '<=', $targetEndDate)
            ->whereDate('end_date', '>=', $targetStartDate)
            ->where(function ($timeQuery) use ($targetStartTime, $targetEndTime) {
                $timeQuery
                    ->whereNull('start_time')
                    ->orWhereNull('end_time')
                    ->orWhere(function ($overlapQuery) use ($targetStartTime, $targetEndTime) {
                        $overlapQuery
                            ->where('start_time', '<', $targetEndTime)
                            ->where('end_time', '>', $targetStartTime);
                    });
            })
            ->exists();

        if ($hasConflict) {
            return redirect()
                ->route('reservations.show', $reservation)
                ->withErrors(['clone_salle_id' => 'Salle indisponible pour la date/heure choisie.'])
                ->withInput();
        }

        $paymentDueDate = Carbon::parse((string) $validated['clone_start_date'])->subDays(30)->toDateString();
        $copyAdditionalServices = (string) $request->input('copy_additional_services', '1') === '1';
        $copySalleOptions = (string) $request->input('copy_salle_options', '1') === '1';

        $reservation->loadMissing(['additionalServices.linkedReservation', 'salleOptionRows']);

        $hasServiceSlugColumn = Schema::hasColumn('reservations', 'service_slug');

        $clonedReservation = DB::transaction(function () use ($reservation, $validated, $paymentDueDate, $copyAdditionalServices, $copySalleOptions, $targetSalleId, $hasServiceSlugColumn): Reservation {
            $newReservationPayload = [
                'client_id' => $reservation->client_id,
                'salle_id' => (int) $validated['clone_salle_id'],
                'user_id' => Auth::id(),
                'title' => (string) $validated['clone_title'],
                'guest_count' => $validated['clone_guest_count'] ?? null,
                'event_type' => (string) $validated['clone_event_type'],
                'start_date' => (string) $validated['clone_start_date'],
                'end_date' => (string) $validated['clone_end_date'],
                'start_time' => (string) $validated['clone_start_time'],
                'end_time' => (string) $validated['clone_end_time'],
                'payment_due_date' => $paymentDueDate,
                'status' => 'pending',
                'total_amount' => $validated['clone_total_amount'] ?? 0,
                'note_admin' => $validated['clone_note_admin'] ?? null,
            ];

            if ($hasServiceSlugColumn) {
                $newReservationPayload['service_slug'] = (string) ($reservation->service_slug ?: 'salles');
            }

            $newReservation = Reservation::query()->create($newReservationPayload);

            if ($copyAdditionalServices) {
                foreach ($reservation->additionalServices as $sourceService) {
                    $linkedReservationId = null;

                    if ($sourceService->linkedReservation) {
                        $linkedTitle = trim(($newReservation->title ?: ('Reservation #' . $newReservation->id)) . ' - ' . $sourceService->label);
                        if ($linkedTitle === '') {
                            $linkedTitle = 'Service supplementaire';
                        }

                        $linkedReservationPayload = [
                            'client_id' => $newReservation->client_id,
                            'salle_id' => $newReservation->salle_id,
                            'user_id' => Auth::id(),
                            'title' => mb_substr($linkedTitle, 0, 255),
                            'start_date' => $newReservation->start_date,
                            'end_date' => $newReservation->end_date,
                            'start_time' => $newReservation->start_time,
                            'end_time' => $newReservation->end_time,
                            'payment_due_date' => $paymentDueDate,
                            'status' => 'pending',
                            'total_amount' => (float) $sourceService->amount,
                            'note_admin' => 'Reservation supplementaire liee a la reservation salle #' . $newReservation->id,
                        ];

                        if ($hasServiceSlugColumn) {
                            $linkedReservationPayload['service_slug'] = $sourceService->module_slug;
                        }

                        $linkedReservation = Reservation::query()->create($linkedReservationPayload);

                        $linkedReservationId = $linkedReservation->id;
                    }

                    ReservationAdditionalService::query()->create([
                        'reservation_id' => $newReservation->id,
                        'linked_reservation_id' => $linkedReservationId,
                        'module_slug' => $sourceService->module_slug,
                        'service_module_item_id' => $sourceService->service_module_item_id,
                        'service_module_pack_id' => $sourceService->service_module_pack_id,
                        'label' => $sourceService->label,
                        'amount' => (float) $sourceService->amount,
                        'note' => $sourceService->note,
                    ]);
                }
            }

            if ($copySalleOptions && (int) $reservation->salle_id === $targetSalleId) {
                foreach ($reservation->salleOptionRows as $sourceOption) {
                    ReservationSalleOption::query()->create([
                        'reservation_id' => $newReservation->id,
                        'salle_option_id' => $sourceOption->salle_option_id,
                        'label' => $sourceOption->label,
                        'amount' => (float) $sourceOption->amount,
                        'note' => $sourceOption->note,
                    ]);
                }
            }

            return $newReservation;
        });

        return redirect()->route('reservations.show', $clonedReservation)->with('success', 'Reservation clonee sans copier les paiements.');
    }

    public function applyClientCredit(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('payments', 'create', 'create');

        if (! Schema::hasTable('client_credit_ledgers')) {
            return redirect()->route('reservations.show', $reservation)->withErrors([
                'credit' => 'Module avoir indisponible. Lance les migrations.',
            ]);
        }

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string'],
        ]);

        $amount = (float) $validated['amount'];
        $serviceSlug = $this->reservationServiceSlug($reservation);
        $balance = $this->getClientCreditBalance((int) $reservation->client_id, $serviceSlug);
        $totalAmount = (float) ($reservation->total_amount ?? 0);
        $alreadyPaid = (float) $reservation->payments()->sum('amount');
        $remaining = max($totalAmount - $alreadyPaid, 0);

        if ($amount > $balance) {
            return redirect()->route('reservations.show', $reservation)->withErrors([
                'credit_amount' => 'Le montant depasse le solde disponible du client pour ce type de service.',
            ])->withInput();
        }

        if ($amount > $remaining) {
            return redirect()->route('reservations.show', $reservation)->withErrors([
                'credit_amount' => 'Le montant depasse le reste a payer de la reservation.',
            ])->withInput();
        }

        DB::transaction(function () use ($reservation, $amount, $validated, $serviceSlug): void {
            $ledgerPayload = [
                'client_id' => $reservation->client_id,
                'reservation_id' => $reservation->id,
                'user_id' => Auth::id(),
                'type' => 'debit',
                'amount' => $amount,
                'description' => 'Utilisation avoir sur reservation #' . $reservation->id,
            ];

            if (Schema::hasColumn('client_credit_ledgers', 'service_slug')) {
                $ledgerPayload['service_slug'] = $serviceSlug;
            }

            ClientCreditLedger::query()->create($ledgerPayload);

            Payment::query()->create([
                'reservation_id' => $reservation->id,
                'user_id' => Auth::id(),
                'amount' => $amount,
                'method' => 'avoir',
                'reference' => 'Avoir client',
                'status' => 'paid',
                'paid_at' => now()->format('Y-m-d H:i:s'),
                'note' => $validated['note'] ?? null,
            ]);
        });

        $totalPaidAfter = (float) $reservation->payments()->sum('amount');
        $remainingAfter = max($totalAmount - $totalPaidAfter, 0);
        if ($remainingAfter <= 0.009 && ! in_array((string) $reservation->status, ['cancelled', 'completed'], true)) {
            $reservation->update(['status' => 'confirmed']);
        }

        return redirect()->route('reservations.show', $reservation)->with('success', 'Avoir applique sur la reservation.');
    }

    public function transferClientCredit(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        if (! Schema::hasTable('client_credit_ledgers')) {
            return redirect()->route('reservations.show', $reservation)->withErrors([
                'credit_transfer' => 'Module transfert d avoir indisponible. Lance les migrations.',
            ]);
        }

        $validated = $request->validate([
            'target_client_id' => ['required', 'integer', 'exists:clients,id', 'different:' . $reservation->client_id],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string'],
        ]);

        $amount = (float) $validated['amount'];
        $sourceClientId = (int) $reservation->client_id;
        $targetClientId = (int) $validated['target_client_id'];
        $serviceSlug = $this->reservationServiceSlug($reservation);
        $sourceBalance = $this->getClientCreditBalance($sourceClientId, $serviceSlug);

        if ($amount > $sourceBalance) {
            return redirect()->route('reservations.show', $reservation)->withErrors([
                'credit_transfer_amount' => 'Le montant depasse le solde disponible a transferer.',
            ])->withInput();
        }

        DB::transaction(function () use ($reservation, $validated, $amount, $sourceClientId, $targetClientId, $serviceSlug): void {
            $transferOutPayload = [
                'client_id' => $sourceClientId,
                'reservation_id' => $reservation->id,
                'user_id' => Auth::id(),
                'type' => 'transfer_out',
                'amount' => $amount,
                'related_client_id' => $targetClientId,
                'description' => $validated['note'] ?? ('Transfert sortant vers client #' . $targetClientId),
            ];

            $transferInPayload = [
                'client_id' => $targetClientId,
                'reservation_id' => null,
                'user_id' => Auth::id(),
                'type' => 'transfer_in',
                'amount' => $amount,
                'related_client_id' => $sourceClientId,
                'description' => $validated['note'] ?? ('Transfert entrant depuis client #' . $sourceClientId),
            ];

            if (Schema::hasColumn('client_credit_ledgers', 'service_slug')) {
                $transferOutPayload['service_slug'] = $serviceSlug;
                $transferInPayload['service_slug'] = $serviceSlug;
            }

            ClientCreditLedger::query()->create($transferOutPayload);

            ClientCreditLedger::query()->create($transferInPayload);
        });

        return redirect()->route('reservations.show', $reservation)->with('success', 'Solde transfere vers le compte client cible.');
    }

    public function store(Request $request)
    {
        $this->enforcePermission('reservations', 'create', 'create');

        $request->merge([
            'start_time' => substr((string) $request->input('start_time', ''), 0, 5),
            'end_time' => substr((string) $request->input('end_time', ''), 0, 5),
        ]);

        $resolvedClientId = $this->resolveReservationClient($request);
        $requestServiceSlug = trim((string) $request->input('service_slug', 'salles'));
        $serviceSlugForValidation = in_array($requestServiceSlug, array_keys(self::RESERVATION_SERVICES), true) ? $requestServiceSlug : 'salles';

        $serviceSpecificLines = [];

        if ($serviceSlugForValidation === 'photographe') {
            $serviceValidated = $request->validate([
                'service_pack_id' => [
                    'required',
                    'integer',
                    Rule::exists('service_module_packs', 'id')->where(function ($query) {
                        $query
                            ->where('module_slug', 'photographe')
                            ->where('status', 'active');
                    }),
                ],
            ]);

            $selectedPack = ServiceModulePack::query()->find((int) $serviceValidated['service_pack_id']);
            if ($selectedPack) {
                $serviceSpecificLines[] = 'Pack photographe: ' . $selectedPack->name;
            }
        }

        if ($serviceSlugForValidation === 'troupe-musicale') {
            $serviceValidated = $request->validate([
                'service_pack_id' => [
                    'required',
                    'integer',
                    Rule::exists('service_module_packs', 'id')->where(function ($query) {
                        $query
                            ->where('module_slug', 'troupe-musicale')
                            ->where('status', 'active');
                    }),
                ],
                'partner_artist_ids' => ['required', 'array', 'min:1'],
                'partner_artist_ids.*' => [
                    'required',
                    'integer',
                    'distinct',
                    Rule::exists('service_module_items', 'id')->where(function ($query) {
                        $query
                            ->where('module_slug', 'chanteur')
                            ->where('status', 'active');
                    }),
                ],
            ]);

            $selectedPack = ServiceModulePack::query()->find((int) $serviceValidated['service_pack_id']);
            $artistNames = ServiceModuleItem::query()
                ->whereIn('id', $serviceValidated['partner_artist_ids'])
                ->pluck('name')
                ->all();

            if ($selectedPack) {
                $serviceSpecificLines[] = 'Pack troupe: ' . $selectedPack->name;
            }
            if (! empty($artistNames)) {
                $serviceSpecificLines[] = 'Artistes partenaires: ' . implode(', ', $artistNames);
            }
        }

        if ($serviceSlugForValidation === 'voiture') {
            $serviceValidated = $request->validate([
                'itinerary_departure' => ['required', 'string', 'max:255'],
                'itinerary_stops' => ['nullable', 'string', 'max:1000'],
                'itinerary_arrival' => ['required', 'string', 'max:255'],
            ]);

            $serviceSpecificLines[] = 'Itineraire voiture - Depart: ' . $serviceValidated['itinerary_departure'];
            if (! empty($serviceValidated['itinerary_stops'])) {
                $serviceSpecificLines[] = 'Itineraire voiture - Arrets: ' . $serviceValidated['itinerary_stops'];
            }
            $serviceSpecificLines[] = 'Itineraire voiture - Arrivee: ' . $serviceValidated['itinerary_arrival'];
        }

        if (in_array($serviceSlugForValidation, ['chanteur', 'notaire', 'animation'], true)) {
            $serviceValidated = $request->validate([
                'service_provider_id' => [
                    'required',
                    'integer',
                    Rule::exists('service_module_items', 'id')->where(function ($query) use ($serviceSlugForValidation) {
                        $query
                            ->where('module_slug', $serviceSlugForValidation)
                            ->where('status', 'active');
                    }),
                ],
            ]);

            $selectedProvider = ServiceModuleItem::query()->find((int) $serviceValidated['service_provider_id']);
            if ($selectedProvider) {
                $serviceSpecificLines[] = 'Prestataire: ' . $selectedProvider->name;
            }
        }

        $validated = $request->validate([
            'salle_id' => ['required', 'exists:salles,id'],
            'service_slug' => ['nullable', Rule::in(array_keys(self::RESERVATION_SERVICES))],
            'title' => ['required', 'string', 'max:255'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'event_type' => ['required', Rule::in(self::EVENT_TYPES)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', 'after_or_equal:08:00', 'before_or_equal:23:59'],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
                'before_or_equal:23:59',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $startRaw = substr((string) $request->input('start_time'), 0, 5);
                    $endRaw = substr((string) $value, 0, 5);

                    $start = Carbon::createFromFormat('H:i', $startRaw);
                    $end = Carbon::createFromFormat('H:i', $endRaw);

                    if ($start && $end && $start->diffInMinutes($end, false) < 60) {
                        $fail('Heure fin doit etre au moins heure debut + 1 heure.');
                    }
                },
            ],
            'payment_due_date' => ['nullable', 'date'],
            'note_admin' => ['nullable', 'string'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['payment_due_date'] = Carbon::parse((string) $validated['start_date'])->subDays(30)->toDateString();

        if (Schema::hasColumn('reservations', 'service_slug')) {
            $validated['service_slug'] = $validated['service_slug'] ?? 'salles';
        } else {
            unset($validated['service_slug']);
        }

        if (! empty($serviceSpecificLines)) {
            $serviceSpecificNote = '[Details service]' . PHP_EOL . implode(PHP_EOL, $serviceSpecificLines);
            $existingAdminNote = trim((string) ($validated['note_admin'] ?? ''));
            $validated['note_admin'] = $existingAdminNote !== ''
                ? ($existingAdminNote . PHP_EOL . PHP_EOL . $serviceSpecificNote)
                : $serviceSpecificNote;
        }

        $validated['client_id'] = $resolvedClientId;

        Reservation::create($validated);

        return redirect()->route('reservations.index')->with('success', 'Reservation creee.');
    }

    public function updateDetails(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'event_type' => ['required', Rule::in(self::EVENT_TYPES)],
            'note_admin' => ['nullable', 'string'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $reservation->update($validated);

        return redirect()->route('reservations.show', $reservation)->with('success', 'Informations de la reservation mises a jour.');
    }

    public function updateSlot(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        $request->merge([
            'start_time' => substr((string) $request->input('start_time', ''), 0, 5),
            'end_time' => substr((string) $request->input('end_time', ''), 0, 5),
        ]);

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
                    $startRaw = substr((string) $request->input('start_time'), 0, 5);
                    $endRaw = substr((string) $value, 0, 5);

                    $start = Carbon::createFromFormat('H:i', $startRaw);
                    $end = Carbon::createFromFormat('H:i', $endRaw);

                    if ($start && $end && $start->diffInMinutes($end, false) < 60) {
                        $fail('Heure fin doit etre au moins heure debut + 1 heure.');
                    }
                },
            ],
            'sync_linked_date_ids' => ['nullable', 'array'],
            'sync_linked_date_ids.*' => ['integer', 'exists:reservations,id'],
        ]);

        $targetSalleId = (int) $validated['salle_id'];
        $targetStartDate = (string) $validated['start_date'];
        $targetEndDate = (string) $validated['end_date'];
        $targetStartTime = (string) $validated['start_time'];
        $targetEndTime = (string) $validated['end_time'];

        $hasConflict = Reservation::query()
            ->where('id', '!=', $reservation->id)
            ->where('salle_id', $targetSalleId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('start_date', '<=', $targetEndDate)
            ->whereDate('end_date', '>=', $targetStartDate)
            ->where(function ($timeQuery) use ($targetStartTime, $targetEndTime) {
                $timeQuery
                    ->whereNull('start_time')
                    ->orWhereNull('end_time')
                    ->orWhere(function ($overlapQuery) use ($targetStartTime, $targetEndTime) {
                        $overlapQuery
                            ->where('start_time', '<', $targetEndTime)
                            ->where('end_time', '>', $targetStartTime);
                    });
            })
            ->exists();

        if ($hasConflict) {
            return redirect()
                ->route('reservations.show', $reservation)
                ->withErrors(['salle_id' => 'Salle indisponible pour la date/heure choisie. Verifie la disponibilite avant modification.'])
                ->withInput();
        }

        $validated['payment_due_date'] = Carbon::parse((string) $validated['start_date'])->subDays(30)->toDateString();

        $reservation->loadMissing(['additionalServices.linkedReservation']);
        $allowedLinkedRows = $reservation->additionalServices
            ->filter(fn (ReservationAdditionalService $row) => (int) ($row->linked_reservation_id ?? 0) > 0)
            ->keyBy(fn (ReservationAdditionalService $row) => (int) $row->linked_reservation_id);

        $selectedLinkedDateSyncIds = collect($validated['sync_linked_date_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        $forbiddenSyncSelection = $selectedLinkedDateSyncIds
            ->first(fn (int $id) => ! $allowedLinkedRows->has($id));

        if ($forbiddenSyncSelection !== null) {
            return redirect()->route('reservations.show', $reservation)->withErrors([
                'sync_linked_date_ids' => 'Selection de reservation liee invalide.',
            ])->withInput();
        }

        $syncedLinkedCount = 0;

        DB::transaction(function () use ($reservation, $validated, $selectedLinkedDateSyncIds, $allowedLinkedRows, &$syncedLinkedCount): void {
            $reservation->update([
                'salle_id' => $validated['salle_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'payment_due_date' => $validated['payment_due_date'],
            ]);

            foreach ($selectedLinkedDateSyncIds as $linkedReservationId) {
                /** @var ReservationAdditionalService|null $linkedRow */
                $linkedRow = $allowedLinkedRows->get($linkedReservationId);
                $linkedReservation = $linkedRow?->linkedReservation;

                if (! $linkedReservation || (string) $linkedReservation->status === 'cancelled') {
                    continue;
                }

                $linkedReservation->update([
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'payment_due_date' => $validated['payment_due_date'],
                ]);

                $syncedLinkedCount++;
            }
        });

        $successMessage = 'Date, heure et salle mises a jour.';
        if ($syncedLinkedCount > 0) {
            $successMessage .= ' ' . $syncedLinkedCount . ' reservation(s) de service supplementaire ont ete alignees sur la date (heures conservees).';
        }

        return redirect()->route('reservations.show', $reservation)->with('success', $successMessage);
    }

    public function update(Request $request, Reservation $reservation)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        $request->merge([
            'start_time' => substr((string) $request->input('start_time', ''), 0, 5),
            'end_time' => substr((string) $request->input('end_time', ''), 0, 5),
        ]);

        $validated = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'salle_id' => ['required', 'exists:salles,id'],
            'service_slug' => ['nullable', Rule::in(array_keys(self::RESERVATION_SERVICES))],
            'title' => ['required', 'string', 'max:255'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'event_type' => ['required', Rule::in(self::EVENT_TYPES)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', 'after_or_equal:08:00', 'before_or_equal:23:59'],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
                'before_or_equal:23:59',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $startRaw = substr((string) $request->input('start_time'), 0, 5);
                    $endRaw = substr((string) $value, 0, 5);

                    $start = Carbon::createFromFormat('H:i', $startRaw);
                    $end = Carbon::createFromFormat('H:i', $endRaw);

                    if ($start && $end && $start->diffInMinutes($end, false) < 60) {
                        $fail('Heure fin doit etre au moins heure debut + 1 heure.');
                    }
                },
            ],
            'payment_due_date' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(['pending', 'confirmed', 'cancelled', 'completed'])],
            'note_admin' => ['nullable', 'string'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $validated['payment_due_date'] = Carbon::parse((string) $validated['start_date'])->subDays(30)->toDateString();

        if (Schema::hasColumn('reservations', 'service_slug')) {
            $validated['service_slug'] = $validated['service_slug'] ?? ($reservation->service_slug ?: 'salles');
        } else {
            unset($validated['service_slug']);
        }

        // Verifier la disponibilite sur le creneau cible (meme logique que updateSalle).
        // Cela couvre toute modification de salle/date/heure depuis n'importe quel formulaire.
        $targetSalleId = (int) $validated['salle_id'];
        $targetStartDate = (string) $validated['start_date'];
        $targetEndDate = (string) $validated['end_date'];
        $targetStartTime = (string) $validated['start_time'];
        $targetEndTime = (string) $validated['end_time'];

        $hasConflict = Reservation::query()
            ->where('id', '!=', $reservation->id)
            ->where('salle_id', $targetSalleId)
            ->where('status', '!=', 'cancelled')
            ->whereDate('start_date', '<=', $targetEndDate)
            ->whereDate('end_date', '>=', $targetStartDate)
            ->where(function ($timeQuery) use ($targetStartTime, $targetEndTime) {
                $timeQuery
                    ->whereNull('start_time')
                    ->orWhereNull('end_time')
                    ->orWhere(function ($overlapQuery) use ($targetStartTime, $targetEndTime) {
                        $overlapQuery
                            ->where('start_time', '<', $targetEndTime)
                            ->where('end_time', '>', $targetStartTime);
                    });
            })
            ->exists();

        if ($hasConflict) {
            return redirect()
                ->route('reservations.show', $reservation)
                ->withErrors(['salle_id' => 'Salle indisponible pour la date/heure choisie. Verifie la disponibilite avant modification.'])
                ->withInput();
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

        $request->merge([
            'start_time' => substr((string) $request->input('start_time', ''), 0, 5),
            'end_time' => substr((string) $request->input('end_time', ''), 0, 5),
        ]);

        $validator = Validator::make($request->all(), [
            'event_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i', 'after_or_equal:08:00', 'before_or_equal:23:59'],
            'end_time' => [
                'required',
                'date_format:H:i',
                'after:start_time',
                'before_or_equal:23:59',
                function (string $attribute, mixed $value, \Closure $fail) use ($request) {
                    $startRaw = substr((string) $request->input('start_time'), 0, 5);
                    $endRaw = substr((string) $value, 0, 5);

                    $start = Carbon::createFromFormat('H:i', $startRaw);
                    $end = Carbon::createFromFormat('H:i', $endRaw);

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
            'source' => ['required', Rule::in(array_keys(self::SOURCES))],
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

    private function getClientCreditBalance(int $clientId, ?string $serviceSlug = null): float
    {
        if (! Schema::hasTable('client_credit_ledgers')) {
            return 0.0;
        }

        $creditQuery = ClientCreditLedger::query()
            ->where('client_id', $clientId)
            ->whereIn('type', ['credit', 'transfer_in']);

        $debitQuery = ClientCreditLedger::query()
            ->where('client_id', $clientId)
            ->whereIn('type', ['debit', 'transfer_out']);

        if ($serviceSlug !== null && Schema::hasColumn('client_credit_ledgers', 'service_slug')) {
            $creditQuery->where('service_slug', $serviceSlug);
            $debitQuery->where('service_slug', $serviceSlug);
        }

        $credit = (float) $creditQuery->sum('amount');

        $debit = (float) $debitQuery->sum('amount');

        return max($credit - $debit, 0);
    }

    private function reservationServiceSlug(Reservation $reservation): string
    {
        $serviceSlug = trim((string) ($reservation->service_slug ?? 'salles'));

        if ($serviceSlug === '' || ! array_key_exists($serviceSlug, self::RESERVATION_SERVICES)) {
            return 'salles';
        }

        return $serviceSlug;
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
