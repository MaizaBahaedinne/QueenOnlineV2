@extends('layouts.app')

@section('content')
    @php
        $canUpdate = auth()->user()?->canFeature('salles', 'update', 'update') ?? false;
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
        <h1 class="panel-title">Options - {{ $salle->name }}</h1>
        <p class="panel-sub">Chaque option est rattachée a cette salle. Le prix peut etre 0 pour gratuit.</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-top:12px;">
            <a href="{{ route('salles.index') }}" class="btn">Retour salles</a>
            @if ($canUpdate)
                <button type="button" class="btn btn-primary" data-open-modal="salle-option-create-modal">Ajouter option</button>
            @endif
        </div>

        <div style="overflow-x:auto; margin-top:12px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Option</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Note</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($options as $option)
                        <tr>
                            <td>{{ $option->id }}</td>
                            <td>{{ $option->name }}</td>
                            <td>{{ number_format((float) $option->price, 2, '.', ' ') }}</td>
                            <td>{{ $option->status ?? 'active' }}</td>
                            <td>{{ $option->note ?: '-' }}</td>
                            <td>
                                <div class="action-row">
                                    @if ($canUpdate)
                                        <button type="button" class="btn" data-open-modal="salle-option-edit-modal"
                                            data-option-id="{{ $option->id }}"
                                            data-option-name="{{ $option->name }}"
                                            data-option-price="{{ $option->price }}"
                                            data-option-status="{{ $option->status ?? 'active' }}"
                                            data-option-note="{{ $option->note }}"
                                        >Modifier</button>
                                        <button type="button" class="btn" data-open-modal="salle-option-delete-modal"
                                            data-option-id="{{ $option->id }}"
                                            data-option-name="{{ $option->name }}"
                                        >Supprimer</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Aucune option pour cette salle.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canUpdate)
        <div class="modal-overlay" id="salle-option-create-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Ajouter option salle</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" action="{{ route('salles.options.store', $salle) }}" style="display:grid; gap:10px;">@csrf
                <input class="search" style="max-width:none;" type="text" name="name" placeholder="Nom option" required>
                <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="price" placeholder="Prix" required>
                <select class="search" style="max-width:none;" name="status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <input class="search" style="max-width:none;" type="text" name="note" placeholder="Note (optionnel)">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form></div></div>

        <div class="modal-overlay" id="salle-option-edit-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Modifier option salle</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" id="salle-option-edit-form" action="#" style="display:grid; gap:10px;">@csrf @method('PATCH')
                <input class="search" style="max-width:none;" type="text" name="name" id="salle-option-edit-name" required>
                <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="price" id="salle-option-edit-price" required>
                <select class="search" style="max-width:none;" name="status" id="salle-option-edit-status" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <input class="search" style="max-width:none;" type="text" name="note" id="salle-option-edit-note">
                <button type="submit" class="btn btn-primary">Mettre a jour</button>
            </form></div></div>

        <div class="modal-overlay" id="salle-option-delete-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Supprimer option salle</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <p id="salle-option-delete-text" class="panel-sub"></p>
            <form method="POST" id="salle-option-delete-form" action="#" style="margin-top:10px;">@csrf @method('DELETE')
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

                if (modalId === 'salle-option-edit-modal') {
                    const optionId = button.dataset.optionId;
                    document.getElementById('salle-option-edit-form').action = `{{ url('salles/' . $salle->id . '/options') }}/${optionId}`;
                    document.getElementById('salle-option-edit-name').value = button.dataset.optionName ?? '';
                    document.getElementById('salle-option-edit-price').value = button.dataset.optionPrice ?? '';
                    document.getElementById('salle-option-edit-status').value = button.dataset.optionStatus ?? 'active';
                    document.getElementById('salle-option-edit-note').value = button.dataset.optionNote ?? '';
                }

                if (modalId === 'salle-option-delete-modal') {
                    const optionId = button.dataset.optionId;
                    document.getElementById('salle-option-delete-form').action = `{{ url('salles/' . $salle->id . '/options') }}/${optionId}`;
                    document.getElementById('salle-option-delete-text').textContent = `Confirmer la suppression de "${button.dataset.optionName}" ?`;
                }
            });
        });

        closeModalButtons.forEach((button) => button.addEventListener('click', () => { const modal = button.closest('.modal-overlay'); if (modal) closeModal(modal); }));
        document.querySelectorAll('.modal-overlay').forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(modal); }));
    </script>
@endsection
