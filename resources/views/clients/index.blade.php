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
        .modal-card { width: min(700px, 100%); max-height: 88vh; overflow: auto; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 16px; box-shadow: var(--shadow); }
        .modal-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
        .modal-title { margin: 0; font-size: 18px; }
        .action-row { display: flex; gap: 8px; flex-wrap: wrap; }
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
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Telephone</th>
                        <th>Ville</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        <tr>
                            <td>{{ $client->id }}</td>
                            <td>{{ $client->name }}</td>
                            <td>{{ $client->email ?? '-' }}</td>
                            <td>{{ $client->phone ?? '-' }}</td>
                            <td>{{ $client->city ?? '-' }}</td>
                            <td>{{ $client->status ?? 'active' }}</td>
                            <td>
                                <div class="action-row">
                                    @if ($canUpdate)
                                        <button type="button" class="btn" data-open-modal="client-edit-modal"
                                            data-client-id="{{ $client->id }}"
                                            data-client-name="{{ $client->name }}"
                                            data-client-email="{{ $client->email }}"
                                            data-client-phone="{{ $client->phone }}"
                                            data-client-cin="{{ $client->cin }}"
                                            data-client-city="{{ $client->city }}"
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
                        <tr><td colspan="7" class="muted">Aucun client.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canCreate)
        <div class="modal-overlay" id="client-create-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Ajouter client</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" action="{{ route('clients.store') }}" style="display:grid; gap:10px;">@csrf
                <input class="search" style="max-width:none;" type="text" name="name" placeholder="Nom" required>
                <input class="search" style="max-width:none;" type="email" name="email" placeholder="Email">
                <input class="search" style="max-width:none;" type="text" name="phone" placeholder="Telephone">
                <input class="search" style="max-width:none;" type="text" name="cin" placeholder="CIN">
                <input class="search" style="max-width:none;" type="text" name="city" placeholder="Ville">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form></div></div>
    @endif

    @if ($canUpdate)
        <div class="modal-overlay" id="client-edit-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Modifier client</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" id="client-edit-form" action="#" style="display:grid; gap:10px;">@csrf @method('PATCH')
                <input class="search" style="max-width:none;" type="text" name="name" id="client-edit-name" required>
                <input class="search" style="max-width:none;" type="email" name="email" id="client-edit-email">
                <input class="search" style="max-width:none;" type="text" name="phone" id="client-edit-phone">
                <input class="search" style="max-width:none;" type="text" name="cin" id="client-edit-cin">
                <input class="search" style="max-width:none;" type="text" name="city" id="client-edit-city">
                <select class="search" style="max-width:none;" name="status" id="client-edit-status"><option value="active">Actif</option><option value="inactive">Inactif</option></select>
                <button type="submit" class="btn btn-primary">Mettre a jour</button>
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

        openModalButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const modalId = button.getAttribute('data-open-modal');
                openModal(modalId);
                if (modalId === 'client-edit-modal') {
                    document.getElementById('client-edit-form').action = `{{ url('clients') }}/${button.dataset.clientId}`;
                    document.getElementById('client-edit-name').value = button.dataset.clientName ?? '';
                    document.getElementById('client-edit-email').value = button.dataset.clientEmail ?? '';
                    document.getElementById('client-edit-phone').value = button.dataset.clientPhone ?? '';
                    document.getElementById('client-edit-cin').value = button.dataset.clientCin ?? '';
                    document.getElementById('client-edit-city').value = button.dataset.clientCity ?? '';
                    document.getElementById('client-edit-status').value = button.dataset.clientStatus ?? 'active';
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
    </script>
@endsection
