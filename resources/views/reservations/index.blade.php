@extends('layouts.app')

@section('content')
    @php
        $canCreate = auth()->user()?->canFeature('reservations', 'create', 'create') ?? false;
        $canUpdate = auth()->user()?->canFeature('reservations', 'update', 'update') ?? false;
        $canDelete = auth()->user()?->canFeature('reservations', 'delete', 'delete') ?? false;
    @endphp

    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 19, 26, 0.56); display: none; align-items: center; justify-content: center; padding: 18px; z-index: 80; }
        .modal-overlay.show { display: flex; }
        .modal-card { width: min(760px, 100%); max-height: 88vh; overflow: auto; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 16px; box-shadow: var(--shadow); }
        .modal-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
        .modal-title { margin: 0; font-size: 18px; }
        .action-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .reservation-helper-box { border: 1px dashed var(--line); border-radius: 10px; padding: 12px; margin-top: 8px; }
        .reservation-helper-title { margin: 0 0 8px; font-size: 14px; font-weight: 700; }
        .reservation-inline-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .reservation-inline-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .reservation-hint { margin: 8px 0 0; font-size: 12px; color: #5f6b7a; }
        .salle-cards-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-top: 10px; }
        .salle-card { border: 1px solid #d7dee8; border-radius: 10px; padding: 10px; cursor: pointer; background: #fff; transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        .salle-card:hover { border-color: #9fb2c8; box-shadow: 0 4px 10px rgba(8, 24, 48, 0.07); transform: translateY(-1px); }
        .salle-card.is-selected { border-color: #245b97; box-shadow: 0 0 0 2px rgba(36, 91, 151, 0.18); }
        .salle-card-name { font-weight: 700; margin-bottom: 4px; }
        .salle-card-meta { font-size: 12px; color: #5f6b7a; }

        @media (max-width: 860px) {
            .reservation-inline-grid,
            .reservation-inline-grid-2,
            .salle-cards-grid { grid-template-columns: 1fr; }
        }
    </style>

    <section class="panel">
        <h1 class="panel-title">Reservations</h1>
        <p class="panel-sub">Liste par defaut. CRUD en modales.</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        <div style="display:flex; justify-content:flex-end; margin-top:12px;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" data-open-modal="reservation-create-modal">Ajouter reservation</button>
            @endif
        </div>

        <div style="overflow-x:auto; margin-top: 12px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Salle</th>
                        <th>Debut</th>
                        <th>Fin</th>
                        <th>Heure debut</th>
                        <th>Heure fin</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reservations as $reservation)
                        <tr>
                            <td>{{ $reservation->id }}</td>
                            <td>{{ $reservation->client?->name ?? '-' }}</td>
                            <td>{{ $reservation->salle?->name ?? '-' }}</td>
                            <td>{{ $reservation->start_date }}</td>
                            <td>{{ $reservation->end_date }}</td>
                            <td>{{ $reservation->start_time ?? '-' }}</td>
                            <td>{{ $reservation->end_time ?? '-' }}</td>
                            <td>{{ $reservation->status ?? 'pending' }}</td>
                            <td><div class="action-row">
                                @if ($canUpdate)
                                    <button type="button" class="btn" data-open-modal="reservation-edit-modal"
                                        data-reservation-id="{{ $reservation->id }}"
                                        data-reservation-client-id="{{ $reservation->client_id }}"
                                        data-reservation-salle-id="{{ $reservation->salle_id }}"
                                        data-reservation-start-date="{{ $reservation->start_date }}"
                                        data-reservation-end-date="{{ $reservation->end_date }}"
                                        data-reservation-start-time="{{ $reservation->start_time }}"
                                        data-reservation-end-time="{{ $reservation->end_time }}"
                                        data-reservation-status="{{ $reservation->status ?? 'pending' }}"
                                        data-reservation-total-amount="{{ $reservation->total_amount }}"
                                    >Modifier</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="btn" data-open-modal="reservation-delete-modal"
                                        data-reservation-id="{{ $reservation->id }}"
                                    >Supprimer</button>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="muted">Aucune reservation.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canCreate)
        <div class="modal-overlay" id="reservation-create-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Ajouter reservation</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" action="{{ route('reservations.store') }}" id="reservation-create-form" style="display:grid; gap:10px;">@csrf
                <div class="reservation-helper-box">
                    <p class="reservation-helper-title">1) Recherche disponibilite salle</p>
                    <div class="reservation-inline-grid">
                        <input class="search" style="max-width:none;" type="date" name="start_date" id="reservation-create-event-date" min="{{ now()->toDateString() }}" required>
                        <input class="search" style="max-width:none;" type="time" name="start_time" id="reservation-create-start-time" min="08:00" max="23:59" required>
                        <input class="search" style="max-width:none;" type="time" name="end_time" id="reservation-create-end-time" min="09:00" max="23:59" required>
                    </div>
                    <input type="hidden" name="end_date" id="reservation-create-end-date">
                    <button type="button" class="btn" id="reservation-check-availability" style="margin-top:10px;">Verifier disponibilite</button>
                    <p class="reservation-hint" id="reservation-availability-status">Selectionne la date et les horaires, puis clique sur verifier.</p>
                    <input type="hidden" name="salle_id" id="reservation-create-salle-id" required>
                    <div id="reservation-salle-cards" class="salle-cards-grid"></div>
                </div>

                <div class="reservation-helper-box">
                    <p class="reservation-helper-title">2) Recherche client</p>
                    <div class="reservation-inline-grid-2">
                        <input class="search" style="max-width:none;" type="text" id="reservation-client-search-input" placeholder="Nom, prenom, CIN ou telephone">
                        <button type="button" class="btn" id="reservation-client-search-btn">Rechercher client</button>
                    </div>

                    <p class="reservation-hint" id="reservation-client-search-status">Recherche un client apres la selection de la salle.</p>
                    <select class="search" style="max-width:none; margin-top:10px;" name="client_id" id="reservation-create-client-id" required disabled>
                        <option value="">Client selectionne</option>
                    </select>

                    <div id="reservation-quick-client-box" style="display:none; margin-top:10px;">
                        <p class="reservation-helper-title" style="margin-bottom:6px;">Client non trouve: ajout rapide</p>
                        <div class="reservation-inline-grid-2">
                            <input class="search" style="max-width:none;" type="text" id="quick-client-first-name" placeholder="Prenom">
                            <input class="search" style="max-width:none;" type="text" id="quick-client-name" placeholder="Nom" required>
                        </div>
                        <div class="reservation-inline-grid-2" style="margin-top:10px;">
                            <input class="search" style="max-width:none;" type="text" id="quick-client-phone" placeholder="Telephone">
                            <input class="search" style="max-width:none;" type="text" id="quick-client-cin" placeholder="CIN">
                        </div>
                        <button type="button" class="btn" id="reservation-quick-client-btn" style="margin-top:10px;">Ajouter ce client</button>
                    </div>
                </div>

                <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="total_amount" placeholder="Montant total">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form></div></div>
    @endif

    @if ($canUpdate)
        <div class="modal-overlay" id="reservation-edit-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Modifier reservation</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" id="reservation-edit-form" action="#" style="display:grid; gap:10px;">@csrf @method('PATCH')
                <select class="search" style="max-width:none;" name="client_id" id="reservation-edit-client-id" required><option value="">Client</option>@foreach ($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select>
                <select class="search" style="max-width:none;" name="salle_id" id="reservation-edit-salle-id" required><option value="">Salle</option>@foreach ($salles as $salle)<option value="{{ $salle->id }}">{{ $salle->name }}</option>@endforeach</select>
                <input class="search" style="max-width:none;" type="date" name="start_date" id="reservation-edit-start-date" min="{{ now()->toDateString() }}" required>
                <input class="search" style="max-width:none;" type="date" name="end_date" id="reservation-edit-end-date" min="{{ now()->toDateString() }}" required>
                <input class="search" style="max-width:none;" type="time" name="start_time" id="reservation-edit-start-time" min="08:00" max="23:59" required>
                <input class="search" style="max-width:none;" type="time" name="end_time" id="reservation-edit-end-time" min="09:00" max="23:59" required>
                <select class="search" style="max-width:none;" name="status" id="reservation-edit-status"><option value="pending">En attente</option><option value="confirmed">Confirmee</option><option value="cancelled">Annulee</option><option value="completed">Terminee</option></select>
                <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="total_amount" id="reservation-edit-total-amount">
                <button type="submit" class="btn btn-primary">Mettre a jour</button>
            </form></div></div>
    @endif

    @if ($canDelete)
        <div class="modal-overlay" id="reservation-delete-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Supprimer reservation</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <p id="reservation-delete-text" class="panel-sub">Confirmer la suppression de cette reservation ?</p>
            <form method="POST" id="reservation-delete-form" action="#" style="margin-top:10px;">@csrf @method('DELETE')
                <button type="submit" class="btn">Confirmer suppression</button>
            </form></div></div>
    @endif

    <script>
        const availabilityUrl = "{{ route('reservations.availability') }}";
        const clientSearchUrl = "{{ route('reservations.clients.search') }}";
        const quickClientStoreUrl = "{{ route('reservations.clients.quick-store') }}";
        const csrfToken = document.querySelector('#reservation-create-form input[name="_token"]')?.value || '';

        const availabilityButton = document.getElementById('reservation-check-availability');
        const availabilityStatus = document.getElementById('reservation-availability-status');
        const eventDateInput = document.getElementById('reservation-create-event-date');
        const startTimeInput = document.getElementById('reservation-create-start-time');
        const endTimeInput = document.getElementById('reservation-create-end-time');
        const endDateInput = document.getElementById('reservation-create-end-date');
        const selectedSalleInput = document.getElementById('reservation-create-salle-id');
        const salleCardsContainer = document.getElementById('reservation-salle-cards');

        const clientSearchInput = document.getElementById('reservation-client-search-input');
        const clientSearchButton = document.getElementById('reservation-client-search-btn');
        const clientSearchStatus = document.getElementById('reservation-client-search-status');
        const clientSelect = document.getElementById('reservation-create-client-id');
        const quickClientBox = document.getElementById('reservation-quick-client-box');
        const quickClientButton = document.getElementById('reservation-quick-client-btn');
        const quickClientFirstNameInput = document.getElementById('quick-client-first-name');
        const quickClientNameInput = document.getElementById('quick-client-name');
        const quickClientPhoneInput = document.getElementById('quick-client-phone');
        const quickClientCinInput = document.getElementById('quick-client-cin');
        const editStartTimeInput = document.getElementById('reservation-edit-start-time');
        const editEndTimeInput = document.getElementById('reservation-edit-end-time');

        const minimumStartTime = '08:00';
        const maximumTime = '23:59';

        const toMinutes = (timeValue) => {
            if (!timeValue || !timeValue.includes(':')) return null;
            const [hourRaw, minuteRaw] = timeValue.split(':');
            const hour = Number(hourRaw);
            const minute = Number(minuteRaw);

            if (Number.isNaN(hour) || Number.isNaN(minute)) return null;
            return (hour * 60) + minute;
        };

        const minutesToTime = (totalMinutes) => {
            if (totalMinutes === null || totalMinutes < 0 || totalMinutes > (23 * 60 + 59)) {
                return null;
            }

            const hour = String(Math.floor(totalMinutes / 60)).padStart(2, '0');
            const minute = String(totalMinutes % 60).padStart(2, '0');
            return `${hour}:${minute}`;
        };

        const getEndTimeMinFromStart = (startValue) => {
            const startMinutes = toMinutes(startValue);

            if (startMinutes === null) {
                return '09:00';
            }

            return minutesToTime(startMinutes + 60) || '23:59';
        };

        const syncEndTimeConstraints = (startInput, endInput) => {
            if (!startInput || !endInput) return;

            const computedMin = getEndTimeMinFromStart(startInput.value);
            endInput.min = computedMin;
            endInput.max = maximumTime;

            if (endInput.value) {
                const currentEnd = toMinutes(endInput.value);
                const requiredEnd = toMinutes(computedMin);

                if (currentEnd === null || requiredEnd === null || currentEnd < requiredEnd) {
                    endInput.value = '';
                }
            }
        };

        const resetSalleSelection = () => {
            if (selectedSalleInput) {
                selectedSalleInput.value = '';
            }

            if (salleCardsContainer) {
                salleCardsContainer.innerHTML = '';
            }
        };

        const hasSelectedSalle = () => {
            return Boolean(selectedSalleInput && selectedSalleInput.value);
        };

        const renderSalleCards = (salles) => {
            if (!salleCardsContainer || !selectedSalleInput) return;

            salleCardsContainer.innerHTML = '';

            if (salles.length === 0) {
                return;
            }

            salles.forEach((salle) => {
                const card = document.createElement('button');
                card.type = 'button';
                card.className = 'salle-card';
                card.dataset.salleId = String(salle.id);
                card.innerHTML = `
                    <div class="salle-card-name">${salle.name}</div>
                    <div class="salle-card-meta">Type: ${salle.salle_type ?? 'standard'} | Capacite: ${salle.capacity ?? '-'} | Prix/jour: ${salle.price_per_day ?? '-'}</div>
                `;

                card.addEventListener('click', () => {
                    salleCardsContainer.querySelectorAll('.salle-card').forEach((node) => node.classList.remove('is-selected'));
                    card.classList.add('is-selected');
                    selectedSalleInput.value = String(salle.id);
                    clientSearchStatus.textContent = 'Salle selectionnee. Tu peux maintenant rechercher un client.';
                });

                salleCardsContainer.appendChild(card);
            });
        };

        const resetClientSelect = () => {
            if (!clientSelect) return;
            clientSelect.innerHTML = '<option value="">Client selectionne</option>';
            clientSelect.value = '';
            clientSelect.disabled = true;
        };

        const fillClientSelect = (clients) => {
            resetClientSelect();

            clients.forEach((client) => {
                const option = document.createElement('option');
                option.value = String(client.id);
                option.textContent = client.label;
                clientSelect.appendChild(option);
            });

            clientSelect.disabled = clients.length === 0;

            if (clients.length > 0) {
                clientSelect.selectedIndex = 1;
            }
        };

        if (availabilityButton) {
            availabilityButton.addEventListener('click', async () => {
                const eventDate = eventDateInput?.value ?? '';
                const startTime = startTimeInput?.value ?? '';
                const endTime = endTimeInput?.value ?? '';

                resetSalleSelection();
                resetClientSelect();
                quickClientBox.style.display = 'none';

                if (!eventDate || !startTime || !endTime) {
                    availabilityStatus.textContent = 'Renseigne date, heure debut et heure fin.';
                    return;
                }

                const today = new Date().toISOString().slice(0, 10);
                if (eventDate < today) {
                    availabilityStatus.textContent = 'Date event doit etre aujourd hui ou plus.';
                    return;
                }

                if (startTime < minimumStartTime || startTime > maximumTime) {
                    availabilityStatus.textContent = 'Heure debut doit etre entre 08:00 et 23:59.';
                    return;
                }

                if (startTime >= endTime) {
                    availabilityStatus.textContent = 'L heure de fin doit etre apres l heure de debut.';
                    return;
                }

                const startMinutes = toMinutes(startTime);
                const endMinutes = toMinutes(endTime);
                if (startMinutes === null || endMinutes === null || (endMinutes - startMinutes) < 60) {
                    availabilityStatus.textContent = 'Heure fin doit etre au moins heure debut + 1 heure.';
                    return;
                }

                if (endTime > maximumTime) {
                    availabilityStatus.textContent = 'Heure fin ne doit pas depasser 23:59.';
                    return;
                }

                endDateInput.value = eventDate;
                availabilityStatus.textContent = 'Recherche des salles disponibles...';

                try {
                    const params = new URLSearchParams({
                        event_date: eventDate,
                        start_time: startTime,
                        end_time: endTime,
                    });

                    const response = await fetch(`${availabilityUrl}?${params.toString()}`, {
                        headers: {
                            Accept: 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Erreur API disponibilite');
                    }

                    const payload = await response.json();
                    const availableSalles = payload.salles ?? [];

                    renderSalleCards(availableSalles);

                    if (availableSalles.length === 0) {
                        availabilityStatus.textContent = 'Aucune salle disponible pour ce creneau.';
                        return;
                    }

                    availabilityStatus.textContent = `${availableSalles.length} salle(s) disponible(s). Selectionne une salle.`;
                } catch (error) {
                    availabilityStatus.textContent = 'Impossible de verifier la disponibilite pour le moment.';
                }
            });
        }

        if (clientSearchButton) {
            clientSearchButton.addEventListener('click', async () => {
                if (!hasSelectedSalle()) {
                    clientSearchStatus.textContent = 'Selectionne d abord une salle disponible.';
                    return;
                }

                const keyword = (clientSearchInput?.value || '').trim();

                quickClientBox.style.display = 'none';
                resetClientSelect();

                if (keyword.length < 2) {
                    clientSearchStatus.textContent = 'Saisis au moins 2 caracteres pour rechercher un client.';
                    return;
                }

                clientSearchStatus.textContent = 'Recherche client en cours...';

                try {
                    const params = new URLSearchParams({ q: keyword });
                    const response = await fetch(`${clientSearchUrl}?${params.toString()}`, {
                        headers: {
                            Accept: 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Erreur API client');
                    }

                    const payload = await response.json();
                    const foundClients = payload.clients ?? [];

                    if (foundClients.length === 0) {
                        clientSearchStatus.textContent = 'Aucun client trouve. Tu peux l ajouter rapidement ci-dessous.';
                        quickClientBox.style.display = 'block';
                        if (quickClientNameInput && !quickClientNameInput.value) {
                            quickClientNameInput.value = keyword;
                        }
                        return;
                    }

                    fillClientSelect(foundClients);
                    clientSearchStatus.textContent = `${foundClients.length} client(s) trouve(s).`;
                } catch (error) {
                    clientSearchStatus.textContent = 'Impossible de rechercher les clients pour le moment.';
                }
            });
        }

        if (quickClientButton) {
            quickClientButton.addEventListener('click', async () => {
                const name = (quickClientNameInput?.value || '').trim();
                const firstName = (quickClientFirstNameInput?.value || '').trim();
                const phone = (quickClientPhoneInput?.value || '').trim();
                const cin = (quickClientCinInput?.value || '').trim();

                if (!name) {
                    clientSearchStatus.textContent = 'Le nom du client est obligatoire pour l ajout rapide.';
                    return;
                }

                clientSearchStatus.textContent = 'Ajout rapide client en cours...';

                try {
                    const body = new URLSearchParams({
                        name,
                        first_name: firstName,
                        phone,
                        cin,
                    });

                    const response = await fetch(quickClientStoreUrl, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: body.toString(),
                    });

                    const payload = await response.json();

                    if (!response.ok || !payload.client) {
                        throw new Error(payload.message || 'Erreur ajout client');
                    }

                    fillClientSelect([payload.client]);
                    clientSearchStatus.textContent = payload.message || 'Client ajoute avec succes.';
                    quickClientBox.style.display = 'none';
                } catch (error) {
                    clientSearchStatus.textContent = 'Ajout client impossible. Verifie les donnees (ex: CIN deja utilise).' ;
                }
            });
        }

        if (startTimeInput && endTimeInput) {
            startTimeInput.addEventListener('change', () => syncEndTimeConstraints(startTimeInput, endTimeInput));
            startTimeInput.addEventListener('input', () => syncEndTimeConstraints(startTimeInput, endTimeInput));
            syncEndTimeConstraints(startTimeInput, endTimeInput);
        }

        if (editStartTimeInput && editEndTimeInput) {
            editStartTimeInput.addEventListener('change', () => syncEndTimeConstraints(editStartTimeInput, editEndTimeInput));
            editStartTimeInput.addEventListener('input', () => syncEndTimeConstraints(editStartTimeInput, editEndTimeInput));
            syncEndTimeConstraints(editStartTimeInput, editEndTimeInput);
        }

        const openModalButtons = document.querySelectorAll('[data-open-modal]');
        const closeModalButtons = document.querySelectorAll('[data-close-modal]');
        const openModal = (id) => { const m = document.getElementById(id); if (m) m.classList.add('show'); };
        const closeModal = (m) => m.classList.remove('show');
        openModalButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const modalId = button.getAttribute('data-open-modal');
                openModal(modalId);
                if (modalId === 'reservation-edit-modal') {
                    document.getElementById('reservation-edit-form').action = `{{ url('reservations') }}/${button.dataset.reservationId}`;
                    document.getElementById('reservation-edit-client-id').value = button.dataset.reservationClientId ?? '';
                    document.getElementById('reservation-edit-salle-id').value = button.dataset.reservationSalleId ?? '';
                    document.getElementById('reservation-edit-start-date').value = button.dataset.reservationStartDate ?? '';
                    document.getElementById('reservation-edit-end-date').value = button.dataset.reservationEndDate ?? '';
                    document.getElementById('reservation-edit-start-time').value = button.dataset.reservationStartTime ?? '';
                    document.getElementById('reservation-edit-end-time').value = button.dataset.reservationEndTime ?? '';
                    syncEndTimeConstraints(editStartTimeInput, editEndTimeInput);
                    document.getElementById('reservation-edit-status').value = button.dataset.reservationStatus ?? 'pending';
                    document.getElementById('reservation-edit-total-amount').value = button.dataset.reservationTotalAmount ?? '';
                }
                if (modalId === 'reservation-delete-modal') {
                    document.getElementById('reservation-delete-form').action = `{{ url('reservations') }}/${button.dataset.reservationId}`;
                }

                if (modalId === 'reservation-create-modal') {
                    resetSalleSelection();
                    resetClientSelect();
                    availabilityStatus.textContent = 'Selectionne la date et les horaires, puis clique sur verifier.';
                    clientSearchStatus.textContent = 'Recherche un client apres la selection de la salle.';
                    quickClientBox.style.display = 'none';
                    endDateInput.value = '';
                }
            });
        });
        closeModalButtons.forEach((button) => button.addEventListener('click', () => { const modal = button.closest('.modal-overlay'); if (modal) closeModal(modal); }));
        document.querySelectorAll('.modal-overlay').forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(modal); }));
    </script>
@endsection
