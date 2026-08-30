@extends('layouts.app')

@section('content')
    @php
        $canCreate = auth()->user()?->canFeature('clients', 'create', 'create') ?? false;
        $canUpdate = auth()->user()?->canFeature('clients', 'update', 'update') ?? false;
        $canDelete = auth()->user()?->canFeature('clients', 'delete', 'delete') ?? false;
        $transferClients = $clients->map(function ($client) {
            $name = trim((string) (($client->first_name ?? '') . ' ' . ($client->name ?? '')));
            $label = $name !== '' ? $name : ($client->name ?? ('Client #' . $client->id));
            if (! empty($client->cin)) {
                $label .= ' - CIN: ' . $client->cin;
            }

            return [
                'id' => (int) $client->id,
                'label' => $label,
            ];
        })->values();
        $creditServiceLabels = is_array($creditServiceLabels ?? null) ? $creditServiceLabels : [];
        $clientCreditBalancesByService = is_array($clientCreditBalancesByService ?? null) ? $clientCreditBalancesByService : [];
    @endphp

    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 19, 26, 0.56); display: none; align-items: center; justify-content: center; padding: 18px; z-index: 80; }
        .modal-overlay.show { display: flex; }
        .modal-card { width: min(840px, 100%); max-height: 88vh; overflow: auto; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 16px; box-shadow: var(--shadow); }
        .modal-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
        .modal-title { margin: 0; font-size: 18px; }
        .action-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .form-grid-two { display:grid; gap:10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .full-span { grid-column: 1 / -1; }
        .cin-alert { display:none; margin:0; font-size:12px; color:#b91c1c; }
        .cin-alert.show { display:block; }
        .company-fields { display:none; }
        .company-fields.show { display:contents; }
        .type-radio-group { display:flex; gap:14px; align-items:center; min-height:42px; flex-wrap:wrap; }
        .type-radio { display:inline-flex; align-items:center; gap:6px; font-size:13px; color:#334a62; }

        @media (max-width: 780px) {
            .form-grid-two { grid-template-columns: 1fr; }
        }
    </style>

    <section class="panel">
        <h1 class="panel-title">Clients</h1>
        <p class="panel-sub">Liste par defaut. CRUD en modales.</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        <div style="display:flex; justify-content:flex-end; margin-top:12px;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" data-open-modal="client-create-modal">Ajouter client</button>
            @endif
        </div>

        <div style="overflow-x:auto; margin-top: 12px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Nom</th>
                        <th>Prenom</th>
                        <th>Email</th>
                        <th>Mobile 1</th>
                        <th>Ville</th>
                        <th>Gouvernorat</th>
                        <th>Solde</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        <tr>
                            <td>{{ $client->id }}</td>
                            <td>{{ $client->client_type === 'societe' ? 'Societe' : 'Personne physique' }}</td>
                            <td>{{ $client->name }}</td>
                            <td>{{ $client->first_name ?? '-' }}</td>
                            <td>{{ $client->email ?? '-' }}</td>
                            <td>{{ $client->phone ?? '-' }}</td>
                            <td>{{ $client->city ?? '-' }}</td>
                            <td>{{ $client->governorate ?? '-' }}</td>
                            <td>{{ number_format((float) ($clientCreditBalances[$client->id] ?? 0), 2, '.', ' ') }}</td>
                            <td>{{ $client->status ?? 'active' }}</td>
                            <td>
                                <div class="action-row">
                                    <a href="{{ route('clients.show', $client) }}" class="btn">Profil</a>
                                    @if ($canUpdate)
                                        <button type="button" class="btn" data-open-modal="client-transfer-credit-modal"
                                            data-client-id="{{ $client->id }}"
                                            data-client-name="{{ trim((string) (($client->first_name ?? '') . ' ' . ($client->name ?? ''))) ?: $client->name }}"
                                            data-client-balance="{{ number_format((float) ($clientCreditBalances[$client->id] ?? 0), 2, '.', '') }}"
                                        >Transferer solde</button>
                                    @endif
                                    @if ($canUpdate)
                                        <button type="button" class="btn" data-open-modal="client-edit-modal"
                                            data-client-id="{{ $client->id }}"
                                            data-client-type="{{ $client->client_type ?? 'personne-physique' }}"
                                            data-client-fiscal-number="{{ $client->fiscal_number }}"
                                            data-client-company-name="{{ $client->company_name }}"
                                            data-client-first-name="{{ $client->first_name }}"
                                            data-client-name="{{ $client->name }}"
                                            data-client-email="{{ $client->email }}"
                                            data-client-phone="{{ $client->phone }}"
                                            data-client-phone-label-1="{{ $client->phone_label_1 }}"
                                            data-client-phone-2="{{ $client->phone_2 }}"
                                            data-client-phone-label-2="{{ $client->phone_label_2 }}"
                                            data-client-cin="{{ $client->cin }}"
                                            data-client-date-cin="{{ $client->date_cin }}"
                                            data-client-address-number="{{ $client->address_number }}"
                                            data-client-address-street="{{ $client->address_street }}"
                                            data-client-city="{{ $client->city }}"
                                            data-client-governorate="{{ $client->governorate }}"
                                            data-client-source="{{ $client->source }}"
                                            data-client-status="{{ $client->status ?? 'active' }}"
                                        >Modifier</button>
                                    @endif
                                    @if ($canDelete)
                                        <button type="button" class="btn" data-open-modal="client-delete-modal"
                                            data-client-id="{{ $client->id }}"
                                            data-client-name="{{ $client->name }}"
                                        >Supprimer</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="muted">Aucun client.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canCreate)
        <div class="modal-overlay" id="client-create-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Ajouter client</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" action="{{ route('clients.store') }}" id="client-create-form" class="form-grid-two">@csrf
                <div class="full-span" id="client-create-type">
                    <div class="type-radio-group">
                        <label class="type-radio"><input type="radio" name="client_type" value="personne-physique" checked> Personne physique</label>
                        <label class="type-radio"><input type="radio" name="client_type" value="societe"> Societe</label>
                    </div>
                </div>

                <div class="company-fields" data-company-fields="client-create-type">
                    <input class="search" style="max-width:none;" type="text" name="fiscal_number" placeholder="Matricule fiscale">
                    <input class="search" style="max-width:none;" type="text" name="company_name" placeholder="Raison sociale">
                </div>

                <input class="search" style="max-width:none;" type="text" name="cin" placeholder="CIN (8 chiffres)" id="client-create-cin" minlength="8" maxlength="8" pattern="[0-9]{8}" inputmode="numeric" required>
                <input class="search" style="max-width:none;" type="date" name="date_cin" placeholder="Date delivrance CIN">
                <p class="cin-alert full-span" id="client-create-cin-alert">Ce CIN existe deja dans la base.</p>

                <input class="search" style="max-width:none;" type="text" name="first_name" placeholder="Prenom" required>
                <input class="search" style="max-width:none;" type="text" name="name" placeholder="Nom" required>

                <input class="search" style="max-width:none;" type="text" name="address_number" placeholder="N adresse">
                <input class="search" style="max-width:none;" type="text" name="address_street" placeholder="Rue">

                <input class="search" style="max-width:none;" type="text" name="city" placeholder="Ville">
                <select class="search" style="max-width:none;" name="governorate" required>
                    <option value="">Gouvernorat</option>
                    @foreach ($governorates as $governorate)
                        <option value="{{ $governorate }}">{{ $governorate }}</option>
                    @endforeach
                </select>

                <input class="search" style="max-width:none;" type="text" name="phone" placeholder="Mobile 1" required>
                <input class="search" style="max-width:none;" type="text" name="phone_label_1" placeholder="Label mobile 1">

                <input class="search" style="max-width:none;" type="text" name="phone_2" placeholder="Mobile 2">
                <input class="search" style="max-width:none;" type="text" name="phone_label_2" placeholder="Label mobile 2">

                <input class="search full-span" style="max-width:none;" type="email" name="email" placeholder="Email">

                <select class="search full-span" style="max-width:none;" name="source" required>
                    <option value="">Source</option>
                    <option value="passager">Passager</option>
                    <option value="reseaux-sociaux-web">Reseaux sociaux/web</option>
                    <option value="presence-event">Presence dans un event</option>
                    <option value="recommandation">Recommandation</option>
                    <option value="connaissance-queenpark">Connaissance de QueenPark</option>
                </select>

                <button type="submit" class="btn btn-primary full-span" id="client-create-submit">Enregistrer</button>
            </form></div></div>
    @endif

    @if ($canUpdate)
        <div class="modal-overlay" id="client-edit-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Modifier client</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" id="client-edit-form" action="#" class="form-grid-two">@csrf @method('PATCH')
                <div class="full-span" id="client-edit-type">
                    <div class="type-radio-group">
                        <label class="type-radio"><input type="radio" name="client_type" value="personne-physique"> Personne physique</label>
                        <label class="type-radio"><input type="radio" name="client_type" value="societe"> Societe</label>
                    </div>
                </div>

                <div class="company-fields" data-company-fields="client-edit-type">
                    <input class="search" style="max-width:none;" type="text" name="fiscal_number" id="client-edit-fiscal-number" placeholder="Matricule fiscale">
                    <input class="search" style="max-width:none;" type="text" name="company_name" id="client-edit-company-name" placeholder="Raison sociale">
                </div>

                <input class="search" style="max-width:none;" type="text" name="cin" id="client-edit-cin" minlength="8" maxlength="8" pattern="[0-9]{8}" inputmode="numeric" required>
                <input class="search" style="max-width:none;" type="date" name="date_cin" id="client-edit-date-cin">
                <p class="cin-alert full-span" id="client-edit-cin-alert">Ce CIN existe deja dans la base.</p>

                <input class="search" style="max-width:none;" type="text" name="first_name" id="client-edit-first-name" required>
                <input class="search" style="max-width:none;" type="text" name="name" id="client-edit-name" required>

                <input class="search" style="max-width:none;" type="text" name="address_number" id="client-edit-address-number" placeholder="N adresse">
                <input class="search" style="max-width:none;" type="text" name="address_street" id="client-edit-address-street" placeholder="Rue">

                <input class="search" style="max-width:none;" type="text" name="city" id="client-edit-city" placeholder="Ville">
                <select class="search" style="max-width:none;" name="governorate" id="client-edit-governorate" required>
                    <option value="">Gouvernorat</option>
                    @foreach ($governorates as $governorate)
                        <option value="{{ $governorate }}">{{ $governorate }}</option>
                    @endforeach
                </select>

                <input class="search" style="max-width:none;" type="text" name="phone" id="client-edit-phone" placeholder="Mobile 1" required>
                <input class="search" style="max-width:none;" type="text" name="phone_label_1" id="client-edit-phone-label-1" placeholder="Label mobile 1">

                <input class="search" style="max-width:none;" type="text" name="phone_2" id="client-edit-phone-2" placeholder="Mobile 2">
                <input class="search" style="max-width:none;" type="text" name="phone_label_2" id="client-edit-phone-label-2" placeholder="Label mobile 2">

                <input class="search full-span" style="max-width:none;" type="email" name="email" id="client-edit-email" placeholder="Email">

                <select class="search full-span" style="max-width:none;" name="source" id="client-edit-source" required>
                    <option value="">Source</option>
                    <option value="passager">Passager</option>
                    <option value="reseaux-sociaux-web">Reseaux sociaux/web</option>
                    <option value="presence-event">Presence dans un event</option>
                    <option value="recommandation">Recommandation</option>
                    <option value="connaissance-queenpark">Connaissance de QueenPark</option>
                </select>

                <button type="submit" class="btn btn-primary full-span" id="client-edit-submit">Mettre a jour</button>
            </form></div></div>
    @endif

    @if ($canDelete)
        <div class="modal-overlay" id="client-delete-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Supprimer client</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <p id="client-delete-text" class="panel-sub"></p>
            <form method="POST" id="client-delete-form" action="#" style="margin-top:10px;">@csrf @method('DELETE')
                <button type="submit" class="btn">Confirmer suppression</button>
            </form></div></div>
    @endif

    @if ($canUpdate)
        <div class="modal-overlay" id="client-transfer-credit-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Transferer solde client</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <p class="panel-sub" id="client-transfer-credit-context"></p>
            <form method="POST" id="client-transfer-credit-form" action="#" class="form-grid-two">@csrf
                <div class="full-span">
                    <label>Type de service</label>
                    <select class="search" style="max-width:none;" id="client-transfer-service-slug" name="service_slug" required>
                        @foreach ($creditServiceLabels as $serviceSlug => $serviceLabel)
                            <option value="{{ $serviceSlug }}">{{ $serviceLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="full-span">
                    <label>Client destinataire</label>
                    <select class="search" style="max-width:none;" id="client-transfer-target" name="target_client_id" required></select>
                </div>
                <div>
                    <label>Montant a transferer</label>
                    <input class="search" style="max-width:none;" id="client-transfer-amount" name="amount" type="number" step="0.01" min="0.01" required>
                </div>
                <div>
                    <label>Motif</label>
                    <input class="search" style="max-width:none;" id="client-transfer-note" name="note" type="text" placeholder="Optionnel">
                </div>
                <button type="submit" class="btn btn-primary full-span">Confirmer transfert</button>
            </form></div></div>
    @endif

    <script type="application/json" id="clients-transfer-data">{!! json_encode($transferClients, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    <script type="application/json" id="clients-transfer-balances-by-service-data">{!! json_encode($clientCreditBalancesByService, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    <script>
        const openModalButtons = document.querySelectorAll('[data-open-modal]');
        const closeModalButtons = document.querySelectorAll('[data-close-modal]');
        const transferClientsData = document.getElementById('clients-transfer-data');
        const transferBalancesByServiceData = document.getElementById('clients-transfer-balances-by-service-data');
        let transferClients = [];
        let transferBalancesByService = {};
        if (transferClientsData) {
            try {
                transferClients = JSON.parse(transferClientsData.textContent || '[]');
            } catch (error) {
                transferClients = [];
            }
        }
        if (transferBalancesByServiceData) {
            try {
                transferBalancesByService = JSON.parse(transferBalancesByServiceData.textContent || '{}');
            } catch (error) {
                transferBalancesByService = {};
            }
        }
        const openModal = (id) => { const m = document.getElementById(id); if (m) m.classList.add('show'); };
        const closeModal = (m) => m.classList.remove('show');

        function getClientTypeValue(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return 'personne-physique';
            const checked = container.querySelector('input[name="client_type"]:checked');
            return checked ? checked.value : 'personne-physique';
        }

        function toggleCompanyFields(typeSelectId) {
            const isCompany = getClientTypeValue(typeSelectId) === 'societe';
            document.querySelectorAll(`[data-company-fields="${typeSelectId}"]`).forEach((container) => {
                container.classList.toggle('show', isCompany);
            });
        }

        async function checkCin(inputId, alertId, submitId, ignoreId = null) {
            const cinInput = document.getElementById(inputId);
            const alert = document.getElementById(alertId);
            const submit = document.getElementById(submitId);
            if (!cinInput || !alert || !submit) return;

            const cin = (cinInput.value || '').trim();
            if (!cin) {
                alert.classList.remove('show');
                submit.disabled = false;
                return;
            }

            const params = new URLSearchParams({ cin });
            if (ignoreId) {
                params.set('ignore_id', String(ignoreId));
            }

            try {
                const response = await fetch(`{{ route('clients.cin-check') }}?${params.toString()}`);
                const data = await response.json();
                const exists = !!data.exists;
                alert.classList.toggle('show', exists);
                submit.disabled = exists;
            } catch (error) {
                alert.classList.remove('show');
                submit.disabled = false;
            }
        }

        openModalButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const modalId = button.getAttribute('data-open-modal');
                openModal(modalId);
                if (modalId === 'client-transfer-credit-modal') {
                    const sourceClientId = button.dataset.clientId;
                    const sourceClientName = button.dataset.clientName || `Client #${sourceClientId}`;
                    const sourceBalance = Number(button.dataset.clientBalance || '0');

                    const form = document.getElementById('client-transfer-credit-form');
                    const context = document.getElementById('client-transfer-credit-context');
                    const targetSelect = document.getElementById('client-transfer-target');
                    const amountInput = document.getElementById('client-transfer-amount');
                    const serviceSelect = document.getElementById('client-transfer-service-slug');
                    const balances = transferBalancesByService[String(sourceClientId)] || {};

                    const refreshContext = () => {
                        const selectedService = serviceSelect ? String(serviceSelect.value || 'salles') : 'salles';
                        const scopedBalance = Number(balances[selectedService] || 0);

                        if (context) {
                            context.textContent = `Source: ${sourceClientName} | Solde disponible (${selectedService}): ${scopedBalance.toFixed(2)}`;
                        }

                        if (amountInput) {
                            amountInput.value = '';
                            amountInput.max = scopedBalance > 0 ? String(scopedBalance) : '0';
                        }
                    };

                    if (form) {
                        form.action = `{{ url('clients') }}/${sourceClientId}/transfer-credit`;
                    }

                    if (serviceSelect) {
                        serviceSelect.value = 'salles';
                        serviceSelect.onchange = refreshContext;
                    }
                    refreshContext();

                    if (targetSelect) {
                        targetSelect.innerHTML = '<option value="">Selectionner...</option>';
                        const sourceIdNum = Number(sourceClientId);

                        transferClients.forEach((client) => {
                            if (Number(client.id) === sourceIdNum) {
                                return;
                            }

                            const opt = document.createElement('option');
                            opt.value = String(client.id);
                            opt.textContent = client.label || `Client #${client.id}`;
                            targetSelect.appendChild(opt);
                        });
                    }
                }
                if (modalId === 'client-edit-modal') {
                    document.getElementById('client-edit-form').action = `{{ url('clients') }}/${button.dataset.clientId}`;
                    document.querySelectorAll('#client-edit-type input[name="client_type"]').forEach((radio) => {
                        radio.checked = radio.value === (button.dataset.clientType ?? 'personne-physique');
                    });
                    document.getElementById('client-edit-fiscal-number').value = button.dataset.clientFiscalNumber ?? '';
                    document.getElementById('client-edit-company-name').value = button.dataset.clientCompanyName ?? '';
                    document.getElementById('client-edit-first-name').value = button.dataset.clientFirstName ?? '';
                    document.getElementById('client-edit-name').value = button.dataset.clientName ?? '';
                    document.getElementById('client-edit-email').value = button.dataset.clientEmail ?? '';
                    document.getElementById('client-edit-phone').value = button.dataset.clientPhone ?? '';
                    document.getElementById('client-edit-phone-label-1').value = button.dataset.clientPhoneLabel1 ?? '';
                    document.getElementById('client-edit-phone-2').value = button.dataset.clientPhone2 ?? '';
                    document.getElementById('client-edit-phone-label-2').value = button.dataset.clientPhoneLabel2 ?? '';
                    document.getElementById('client-edit-cin').value = button.dataset.clientCin ?? '';
                    document.getElementById('client-edit-date-cin').value = button.dataset.clientDateCin ?? '';
                    document.getElementById('client-edit-address-number').value = button.dataset.clientAddressNumber ?? '';
                    document.getElementById('client-edit-address-street').value = button.dataset.clientAddressStreet ?? '';
                    document.getElementById('client-edit-city').value = button.dataset.clientCity ?? '';
                    document.getElementById('client-edit-governorate').value = button.dataset.clientGovernorate ?? '';
                    document.getElementById('client-edit-source').value = button.dataset.clientSource ?? '';
                    toggleCompanyFields('client-edit-type');
                    checkCin('client-edit-cin', 'client-edit-cin-alert', 'client-edit-submit', button.dataset.clientId);
                }
                if (modalId === 'client-delete-modal') {
                    document.getElementById('client-delete-form').action = `{{ url('clients') }}/${button.dataset.clientId}`;
                    document.getElementById('client-delete-text').textContent = `Confirmer la suppression de "${button.dataset.clientName}" ?`;
                }
            });
        });

        closeModalButtons.forEach((button) => button.addEventListener('click', () => {
            const modal = button.closest('.modal-overlay');
            if (modal) closeModal(modal);
        }));

        document.querySelectorAll('.modal-overlay').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal(modal);
            });
        });

        const createType = document.getElementById('client-create-type');
        if (createType) {
            toggleCompanyFields('client-create-type');
            createType.querySelectorAll('input[name="client_type"]').forEach((radio) => {
                radio.addEventListener('change', () => toggleCompanyFields('client-create-type'));
            });
        }

        const editType = document.getElementById('client-edit-type');
        if (editType) {
            editType.querySelectorAll('input[name="client_type"]').forEach((radio) => {
                radio.addEventListener('change', () => toggleCompanyFields('client-edit-type'));
            });
        }

        const createCin = document.getElementById('client-create-cin');
        if (createCin) {
            createCin.addEventListener('input', () => checkCin('client-create-cin', 'client-create-cin-alert', 'client-create-submit'));
            createCin.addEventListener('blur', () => checkCin('client-create-cin', 'client-create-cin-alert', 'client-create-submit'));
        }

        const editCin = document.getElementById('client-edit-cin');
        if (editCin) {
            editCin.addEventListener('input', () => {
                const formAction = document.getElementById('client-edit-form')?.action || '';
                const id = formAction.split('/').pop();
                checkCin('client-edit-cin', 'client-edit-cin-alert', 'client-edit-submit', id);
            });
            editCin.addEventListener('blur', () => {
                const formAction = document.getElementById('client-edit-form')?.action || '';
                const id = formAction.split('/').pop();
                checkCin('client-edit-cin', 'client-edit-cin-alert', 'client-edit-submit', id);
            });
        }
    </script>
@endsection
