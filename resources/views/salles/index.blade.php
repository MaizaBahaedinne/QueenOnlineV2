@extends('layouts.app')

@section('content')
    @php
        $canCreate = auth()->user()?->canFeature('salles', 'create', 'create') ?? false;
        $canUpdate = auth()->user()?->canFeature('salles', 'update', 'update') ?? false;
        $canDelete = auth()->user()?->canFeature('salles', 'delete', 'delete') ?? false;
    @endphp

    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 19, 26, 0.56); display: none; align-items: center; justify-content: center; padding: 18px; z-index: 80; }
        .modal-overlay.show { display: flex; }
        .modal-card { width: min(700px, 100%); max-height: 88vh; overflow: auto; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 16px; box-shadow: var(--shadow); }
        .modal-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
        .modal-title { margin: 0; font-size: 18px; }
        .action-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .salle-color-chip { width: 24px; height: 24px; border-radius: 8px; border: 1px solid #cbd5e1; display: inline-block; vertical-align: middle; }
    </style>

    <section class="panel">
        <h1 class="panel-title">Salles</h1>
        <p class="panel-sub">Liste par defaut. CRUD en modales.</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        <div style="display:flex; justify-content:flex-end; margin-top:12px;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" data-open-modal="salle-create-modal">Ajouter salle</button>
            @endif
        </div>

        <div style="overflow-x:auto; margin-top: 12px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Couleur</th>
                        <th>Capacite</th>
                        <th>Prix/Jour</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($salles as $salle)
                        <tr>
                            <td>{{ $salle->id }}</td>
                            <td>{{ $salle->name }}</td>
                            <td>{{ $salle->salle_type === 'plein-air' ? 'En plein air' : 'Couvert' }}</td>
                            <td><span class="salle-color-chip" data-color="{{ $salle->color_code ?? '#3b82f6' }}"></span> {{ $salle->color_code ?? '#3b82f6' }}</td>
                            <td>{{ $salle->capacity }}</td>
                            <td>{{ number_format((float) $salle->price_per_day, 2, '.', ' ') }}</td>
                            <td>{{ $salle->status ?? 'active' }}</td>
                            <td><div class="action-row">
                                @if ($canUpdate)
                                    <button type="button" class="btn" data-open-modal="salle-edit-modal"
                                        data-salle-id="{{ $salle->id }}"
                                        data-salle-name="{{ $salle->name }}"
                                        data-salle-type="{{ $salle->salle_type ?? 'couvert' }}"
                                        data-salle-color-code="{{ $salle->color_code ?? '#3b82f6' }}"
                                        data-salle-capacity="{{ $salle->capacity }}"
                                        data-salle-price="{{ $salle->price_per_day }}"
                                        data-salle-status="{{ $salle->status ?? 'active' }}"
                                        data-salle-location="{{ $salle->location }}"
                                        data-salle-description="{{ $salle->description }}"
                                    >Modifier</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="btn" data-open-modal="salle-delete-modal"
                                        data-salle-id="{{ $salle->id }}"
                                        data-salle-name="{{ $salle->name }}"
                                    >Supprimer</button>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="muted">Aucune salle.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canCreate)
        <div class="modal-overlay" id="salle-create-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Ajouter salle</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" action="{{ route('salles.store') }}" style="display:grid; gap:10px;">@csrf
                <input class="search" style="max-width:none;" type="text" name="name" placeholder="Nom" required>
                <select class="search" style="max-width:none;" name="salle_type" required>
                    <option value="couvert">Couvert</option>
                    <option value="plein-air">En plein air</option>
                </select>
                <input class="search" style="max-width:none;" type="color" name="color_code" value="#3b82f6" required>
                <input class="search" style="max-width:none;" type="number" min="1" name="capacity" placeholder="Capacite" required>
                <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="price_per_day" placeholder="Prix par jour" required>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form></div></div>
    @endif

    @if ($canUpdate)
        <div class="modal-overlay" id="salle-edit-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Modifier salle</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" id="salle-edit-form" action="#" style="display:grid; gap:10px;">@csrf @method('PATCH')
                <input class="search" style="max-width:none;" type="text" name="name" id="salle-edit-name" required>
                <select class="search" style="max-width:none;" name="salle_type" id="salle-edit-type" required>
                    <option value="couvert">Couvert</option>
                    <option value="plein-air">En plein air</option>
                </select>
                <input class="search" style="max-width:none;" type="color" name="color_code" id="salle-edit-color-code" required>
                <input class="search" style="max-width:none;" type="number" min="1" name="capacity" id="salle-edit-capacity" required>
                <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="price_per_day" id="salle-edit-price" required>
                <select class="search" style="max-width:none;" name="status" id="salle-edit-status"><option value="active">Actif</option><option value="inactive">Inactif</option></select>
                <input class="search" style="max-width:none;" type="text" name="location" id="salle-edit-location" placeholder="Localisation">
                <input class="search" style="max-width:none;" type="text" name="description" id="salle-edit-description" placeholder="Description">
                <button type="submit" class="btn btn-primary">Mettre a jour</button>
            </form></div></div>
    @endif

    @if ($canDelete)
        <div class="modal-overlay" id="salle-delete-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Supprimer salle</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <p id="salle-delete-text" class="panel-sub"></p>
            <form method="POST" id="salle-delete-form" action="#" style="margin-top:10px;">@csrf @method('DELETE')
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
                if (modalId === 'salle-edit-modal') {
                    document.getElementById('salle-edit-form').action = `{{ url('salles') }}/${button.dataset.salleId}`;
                    document.getElementById('salle-edit-name').value = button.dataset.salleName ?? '';
                    document.getElementById('salle-edit-type').value = button.dataset.salleType ?? 'couvert';
                    document.getElementById('salle-edit-color-code').value = button.dataset.salleColorCode ?? '#3b82f6';
                    document.getElementById('salle-edit-capacity').value = button.dataset.salleCapacity ?? '';
                    document.getElementById('salle-edit-price').value = button.dataset.sallePrice ?? '';
                    document.getElementById('salle-edit-status').value = button.dataset.salleStatus ?? 'active';
                    document.getElementById('salle-edit-location').value = button.dataset.salleLocation ?? '';
                    document.getElementById('salle-edit-description').value = button.dataset.salleDescription ?? '';
                }
                if (modalId === 'salle-delete-modal') {
                    document.getElementById('salle-delete-form').action = `{{ url('salles') }}/${button.dataset.salleId}`;
                    document.getElementById('salle-delete-text').textContent = `Confirmer la suppression de "${button.dataset.salleName}" ?`;
                }
            });
        });
        closeModalButtons.forEach((button) => button.addEventListener('click', () => { const modal = button.closest('.modal-overlay'); if (modal) closeModal(modal); }));
        document.querySelectorAll('.modal-overlay').forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(modal); }));

        document.querySelectorAll('.salle-color-chip[data-color]').forEach((chip) => {
            const color = chip.getAttribute('data-color') || '#3b82f6';
            chip.style.background = color;
        });
    </script>
@endsection
