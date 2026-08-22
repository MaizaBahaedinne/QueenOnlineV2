@extends('layouts.app')

@section('content')
    @php
        $canCreate = auth()->user()?->canFeature('clients', 'create', 'create') ?? false;
        $canUpdate = auth()->user()?->canFeature('clients', 'update', 'update') ?? false;
        $canDelete = auth()->user()?->canFeature('clients', 'delete', 'delete') ?? false;
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
                            <td>{{ $client->status ?? 'active' }}</td>
                            <td>
                                <div class="action-row">
                                    @if ($canUpdate)
                                        <button type="button" class="btn" data-open-modal="client-edit-modal"
                                            data-client-id="{{ $client->id }}"
                                            data-client-type="{{ $client->client_type ?? 'personne-physique' }}"
                                            data-client-fiscal-number="{{ $client->fiscal_number }}"
                                            data-client-company-name="{{ $client->company_name }}"
                                            data-client-first-name="{{ $client->first_name }}"
                                            data-client-name="{{ $client->name }}"
                                            data-client-gender="{{ $client->gender }}"
                                            data-client-birth-date="{{ $client->birth_date }}"
                                            data-client-email="{{ $client->email }}"
                                            data-client-phone="{{ $client->phone }}"
                                            data-client-phone-label-1="{{ $client->phone_label_1 }}"
                                            data-client-phone-2="{{ $client->phone_2 }}"
                                            data-client-phone-label-2="{{ $client->phone_label_2 }}"
                                            data-client-cin="{{ $client->cin }}"
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
                        <tr><td colspan="10" class="muted">Aucun client.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canCreate)
        <div class="modal-overlay" id="client-create-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Ajouter client</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" action="{{ route('clients.store') }}" id="client-create-form" class="form-grid-two">@csrf
                <select class="search" style="max-width:none;" name="client_type" id="client-create-type" required>
                    <option value="personne-physique">Personne physique</option>
                    <option value="societe">Societe</option>
                </select>
                <select class="search" style="max-width:none;" name="status" required>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </select>

                <div class="company-fields" data-company-fields="client-create-type">
                    <input class="search" style="max-width:none;" type="text" name="fiscal_number" placeholder="Matricule fiscale">
                    <input class="search" style="max-width:none;" type="text" name="company_name" placeholder="Raison sociale">
                </div>

                <input class="search" style="max-width:none;" type="text" name="first_name" placeholder="Prenom" required>
                <input class="search" style="max-width:none;" type="text" name="name" placeholder="Nom" required>

                <select class="search" style="max-width:none;" name="gender" required>
                    <option value="homme">Homme</option>
                    <option value="femme">Femme</option>
                </select>
                <input class="search" style="max-width:none;" type="date" name="birth_date" placeholder="Date de naissance">

                <input class="search" style="max-width:none;" type="text" name="cin" placeholder="CIN" id="client-create-cin" required>
                <p class="cin-alert full-span" id="client-create-cin-alert">Ce CIN existe deja dans la base.</p>

                <input class="search" style="max-width:none;" type="text" name="address_number" placeholder="N adresse">
                <input class="search" style="max-width:none;" type="text" name="address_street" placeholder="Rue">

                <input class="search" style="max-width:none;" type="text" name="city" placeholder="Ville">
                <select class="search" style="max-width:none;" name="governorate" required>
                    <option value="">Gouvernorat</option>
                    @foreach ($governorates as $governorate)
                        <option value="{{ $governorate }}">{{ $governorate }}</option>
                    @endforeach
                </select>

                <input class="search" style="max-width:none;" type="text" name="phone" placeholder="Mobile 1">
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
                <select class="search" style="max-width:none;" name="client_type" id="client-edit-type" required>
                    <option value="personne-physique">Personne physique</option>
                    <option value="societe">Societe</option>
                </select>
                <select class="search" style="max-width:none;" name="status" id="client-edit-status" required>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </select>

                <div class="company-fields" data-company-fields="client-edit-type">
                    <input class="search" style="max-width:none;" type="text" name="fiscal_number" id="client-edit-fiscal-number" placeholder="Matricule fiscale">
                    <input class="search" style="max-width:none;" type="text" name="company_name" id="client-edit-company-name" placeholder="Raison sociale">
                </div>

                <input class="search" style="max-width:none;" type="text" name="first_name" id="client-edit-first-name" required>
                <input class="search" style="max-width:none;" type="text" name="name" id="client-edit-name" required>

                <select class="search" style="max-width:none;" name="gender" id="client-edit-gender" required>
                    <option value="homme">Homme</option>
                    <option value="femme">Femme</option>
                </select>
                <input class="search" style="max-width:none;" type="date" name="birth_date" id="client-edit-birth-date">

                <input class="search" style="max-width:none;" type="text" name="cin" id="client-edit-cin" required>
                <p class="cin-alert full-span" id="client-edit-cin-alert">Ce CIN existe deja dans la base.</p>

                <input class="search" style="max-width:none;" type="text" name="address_number" id="client-edit-address-number" placeholder="N adresse">
                <input class="search" style="max-width:none;" type="text" name="address_street" id="client-edit-address-street" placeholder="Rue">

                <input class="search" style="max-width:none;" type="text" name="city" id="client-edit-city" placeholder="Ville">
                <select class="search" style="max-width:none;" name="governorate" id="client-edit-governorate" required>
                    <option value="">Gouvernorat</option>
                    @foreach ($governorates as $governorate)
                        <option value="{{ $governorate }}">{{ $governorate }}</option>
                    @endforeach
                </select>

                <input class="search" style="max-width:none;" type="text" name="phone" id="client-edit-phone" placeholder="Mobile 1">
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

    <script>
        const openModalButtons = document.querySelectorAll('[data-open-modal]');
        const closeModalButtons = document.querySelectorAll('[data-close-modal]');
        const openModal = (id) => { const m = document.getElementById(id); if (m) m.classList.add('show'); };
        const closeModal = (m) => m.classList.remove('show');

        function toggleCompanyFields(typeSelectId) {
            const select = document.getElementById(typeSelectId);
            if (!select) return;
            const isCompany = select.value === 'societe';
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
                if (modalId === 'client-edit-modal') {
                    document.getElementById('client-edit-form').action = `{{ url('clients') }}/${button.dataset.clientId}`;
                    document.getElementById('client-edit-type').value = button.dataset.clientType ?? 'personne-physique';
                    document.getElementById('client-edit-fiscal-number').value = button.dataset.clientFiscalNumber ?? '';
                    document.getElementById('client-edit-company-name').value = button.dataset.clientCompanyName ?? '';
                    document.getElementById('client-edit-first-name').value = button.dataset.clientFirstName ?? '';
                    document.getElementById('client-edit-name').value = button.dataset.clientName ?? '';
                    document.getElementById('client-edit-gender').value = button.dataset.clientGender ?? 'homme';
                    document.getElementById('client-edit-birth-date').value = button.dataset.clientBirthDate ?? '';
                    document.getElementById('client-edit-email').value = button.dataset.clientEmail ?? '';
                    document.getElementById('client-edit-phone').value = button.dataset.clientPhone ?? '';
                    document.getElementById('client-edit-phone-label-1').value = button.dataset.clientPhoneLabel1 ?? '';
                    document.getElementById('client-edit-phone-2').value = button.dataset.clientPhone2 ?? '';
                    document.getElementById('client-edit-phone-label-2').value = button.dataset.clientPhoneLabel2 ?? '';
                    document.getElementById('client-edit-cin').value = button.dataset.clientCin ?? '';
                    document.getElementById('client-edit-address-number').value = button.dataset.clientAddressNumber ?? '';
                    document.getElementById('client-edit-address-street').value = button.dataset.clientAddressStreet ?? '';
                    document.getElementById('client-edit-city').value = button.dataset.clientCity ?? '';
                    document.getElementById('client-edit-governorate').value = button.dataset.clientGovernorate ?? '';
                    document.getElementById('client-edit-source').value = button.dataset.clientSource ?? '';
                    document.getElementById('client-edit-status').value = button.dataset.clientStatus ?? 'active';
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
            createType.addEventListener('change', () => toggleCompanyFields('client-create-type'));
        }

        const editType = document.getElementById('client-edit-type');
        if (editType) {
            editType.addEventListener('change', () => toggleCompanyFields('client-edit-type'));
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
