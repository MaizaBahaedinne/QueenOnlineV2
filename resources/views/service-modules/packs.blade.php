@extends('layouts.app')

@section('content')
    @php
        $canCreatePack = auth()->user()?->canFeature($moduleSlug, 'create-pack', 'create') ?? false;
        $canUpdatePack = auth()->user()?->canFeature($moduleSlug, 'update-pack', 'update') ?? false;
        $canDeletePack = auth()->user()?->canFeature($moduleSlug, 'delete-pack', 'delete') ?? false;
    @endphp

    <style>
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 19, 26, 0.56);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            z-index: 80;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-card {
            width: min(640px, 100%);
            max-height: 88vh;
            overflow: auto;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
            box-shadow: var(--shadow);
        }

        .modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .modal-title {
            margin: 0;
            font-size: 18px;
        }

        .action-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
    </style>

    <section class="panel">
        <h1 class="panel-title">{{ $moduleMeta['name'] }} - Packs</h1>
        <p class="panel-sub">Interface dediee a la gestion des packs.</p>

        <div style="margin-top:12px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
            <a class="btn" href="{{ route('service-modules.show', $moduleSlug) }}">Retour aux services</a>
            @if ($canCreatePack)
                <button type="button" class="btn btn-primary" data-open-modal="pack-create-modal">Ajouter pack</button>
            @endif
        </div>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        <div style="overflow-x:auto; margin-top:14px;">
            <table>
                <thead>
                    <tr>
                        <th>Pack</th>
                        <th>Service lie</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packs as $pack)
                        <tr>
                            <td>{{ $pack->name }}</td>
                            <td>{{ $pack->item?->name ?? '-' }}</td>
                            <td>{{ number_format((float) $pack->price, 2, '.', ' ') }}</td>
                            <td>{{ $pack->status }}</td>
                            <td>{{ $pack->description ?? '-' }}</td>
                            <td>
                                <div class="action-row">
                                    @if ($canUpdatePack)
                                        <button
                                            type="button"
                                            class="btn"
                                            data-open-modal="pack-edit-modal"
                                            data-pack-id="{{ $pack->id }}"
                                            data-pack-item-id="{{ $pack->service_module_item_id }}"
                                            data-pack-name="{{ $pack->name }}"
                                            data-pack-price="{{ $pack->price }}"
                                            data-pack-status="{{ $pack->status }}"
                                            data-pack-description="{{ $pack->description }}"
                                        >
                                            Modifier
                                        </button>
                                    @endif
                                    @if ($canDeletePack)
                                        <button
                                            type="button"
                                            class="btn"
                                            data-open-modal="pack-delete-modal"
                                            data-pack-id="{{ $pack->id }}"
                                            data-pack-name="{{ $pack->name }}"
                                        >
                                            Supprimer
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted">Aucun pack.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canCreatePack)
        <div class="modal-overlay" id="pack-create-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Ajouter un pack</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>
                <form method="POST" action="{{ route('service-modules.packs.store', $moduleSlug) }}" style="display:grid; gap:10px;">
                    @csrf
                    <select class="search" style="max-width:none;" name="service_module_item_id">
                        <option value="">Sans service</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    <input class="search" style="max-width:none;" type="text" name="name" placeholder="Nom du pack" required>
                    <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="price" placeholder="Prix">
                    <select class="search" style="max-width:none;" name="status" required>
                        <option value="active">Actif</option>
                        <option value="inactive">Inactif</option>
                    </select>
                    <input class="search" style="max-width:none;" type="text" name="description" placeholder="Description">
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </form>
            </div>
        </div>
    @endif

    @if ($canUpdatePack)
        <div class="modal-overlay" id="pack-edit-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Modifier pack</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>
                <form method="POST" id="pack-edit-form" action="#" style="display:grid; gap:10px;">
                    @csrf
                    @method('PATCH')
                    <select class="search" style="max-width:none;" name="service_module_item_id" id="pack-edit-item-id">
                        <option value="">Sans service</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    <input class="search" style="max-width:none;" type="text" name="name" id="pack-edit-name" required>
                    <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="price" id="pack-edit-price">
                    <select class="search" style="max-width:none;" name="status" id="pack-edit-status" required>
                        <option value="active">Actif</option>
                        <option value="inactive">Inactif</option>
                    </select>
                    <input class="search" style="max-width:none;" type="text" name="description" id="pack-edit-description">
                    <button type="submit" class="btn btn-primary">Mettre a jour</button>
                </form>
            </div>
        </div>
    @endif

    @if ($canDeletePack)
        <div class="modal-overlay" id="pack-delete-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Supprimer pack</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>
                <p id="pack-delete-text" class="panel-sub"></p>
                <form method="POST" id="pack-delete-form" action="#" style="margin-top:10px;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn">Confirmer suppression</button>
                </form>
            </div>
        </div>
    @endif

    <script>
        const openModalButtons = document.querySelectorAll('[data-open-modal]');
        const closeModalButtons = document.querySelectorAll('[data-close-modal]');

        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('show');
            }
        }

        function closeModal(modal) {
            modal.classList.remove('show');
        }

        openModalButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const modalId = button.getAttribute('data-open-modal');
                openModal(modalId);

                if (modalId === 'pack-edit-modal') {
                    document.getElementById('pack-edit-form').action = `{{ url('service-modules/'.$moduleSlug.'/packs') }}/${button.dataset.packId}`;
                    document.getElementById('pack-edit-item-id').value = button.dataset.packItemId ?? '';
                    document.getElementById('pack-edit-name').value = button.dataset.packName ?? '';
                    document.getElementById('pack-edit-price').value = button.dataset.packPrice ?? '';
                    document.getElementById('pack-edit-status').value = button.dataset.packStatus ?? 'active';
                    document.getElementById('pack-edit-description').value = button.dataset.packDescription ?? '';
                }

                if (modalId === 'pack-delete-modal') {
                    document.getElementById('pack-delete-form').action = `{{ url('service-modules/'.$moduleSlug.'/packs') }}/${button.dataset.packId}`;
                    document.getElementById('pack-delete-text').textContent = `Confirmer la suppression de "${button.dataset.packName}" ?`;
                }
            });
        });

        closeModalButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest('.modal-overlay');
                if (modal) {
                    closeModal(modal);
                }
            });
        });

        document.querySelectorAll('.modal-overlay').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal(modal);
                }
            });
        });
    </script>
@endsection
