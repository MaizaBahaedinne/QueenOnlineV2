@extends('layouts.app')

@section('content')
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
        <h1 class="panel-title">{{ $moduleMeta['name'] }}</h1>
        <p class="panel-sub">Module metier operationnel (donnees + CRUD).</p>

        @if ($moduleMeta['packs'])
            <div style="margin-top:12px; display:flex; justify-content:flex-end;">
                <a class="btn" href="{{ route('service-modules.packs.index', $moduleSlug) }}">Gerer les packs</a>
            </div>
        @endif

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        <div class="content-grid" style="margin-top: 14px;">
            <div class="panel" style="box-shadow:none;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                    <h2 class="panel-title" style="margin:0;">Elements</h2>
                    <button type="button" class="btn btn-primary" data-open-modal="item-create-modal">Ajouter element</button>
                </div>
                <div style="overflow-x:auto; margin-top:10px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Telephone</th>
                                <th>Prix base</th>
                                <th>Statut</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->phone ?? '-' }}</td>
                                    <td>{{ number_format((float) $item->base_price, 2, '.', ' ') }}</td>
                                    <td>{{ $item->status }}</td>
                                    <td>{{ $item->notes ?? '-' }}</td>
                                    <td>
                                        <div class="action-row">
                                            <button
                                                type="button"
                                                class="btn"
                                                data-open-modal="item-edit-modal"
                                                data-item-id="{{ $item->id }}"
                                                data-item-name="{{ $item->name }}"
                                                data-item-phone="{{ $item->phone }}"
                                                data-item-price="{{ $item->base_price }}"
                                                data-item-status="{{ $item->status }}"
                                                data-item-notes="{{ $item->notes }}"
                                            >
                                                Modifier
                                            </button>
                                            <button
                                                type="button"
                                                class="btn"
                                                data-open-modal="item-delete-modal"
                                                data-item-id="{{ $item->id }}"
                                                data-item-name="{{ $item->name }}"
                                            >
                                                Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="muted">Aucun element.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>

    <div class="modal-overlay" id="item-create-modal">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="modal-title">Ajouter un element</h3>
                <button type="button" class="btn" data-close-modal>Fermer</button>
            </div>
            <form method="POST" action="{{ route('service-modules.items.store', $moduleSlug) }}" style="display:grid; gap:10px;">
                @csrf
                <input class="search" style="max-width:none;" type="text" name="name" placeholder="Nom" required>
                <input class="search" style="max-width:none;" type="text" name="phone" placeholder="Telephone">
                <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="base_price" placeholder="Prix de base">
                <select class="search" style="max-width:none;" name="status" required>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </select>
                <input class="search" style="max-width:none;" type="text" name="notes" placeholder="Notes">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="item-edit-modal">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="modal-title">Modifier element</h3>
                <button type="button" class="btn" data-close-modal>Fermer</button>
            </div>
            <form method="POST" id="item-edit-form" action="#" style="display:grid; gap:10px;">
                @csrf
                @method('PATCH')
                <input class="search" style="max-width:none;" type="text" name="name" id="item-edit-name" required>
                <input class="search" style="max-width:none;" type="text" name="phone" id="item-edit-phone">
                <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="base_price" id="item-edit-price">
                <select class="search" style="max-width:none;" name="status" id="item-edit-status" required>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </select>
                <input class="search" style="max-width:none;" type="text" name="notes" id="item-edit-notes">
                <button type="submit" class="btn btn-primary">Mettre a jour</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="item-delete-modal">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="modal-title">Supprimer element</h3>
                <button type="button" class="btn" data-close-modal>Fermer</button>
            </div>
            <p id="item-delete-text" class="panel-sub"></p>
            <form method="POST" id="item-delete-form" action="#" style="margin-top:10px;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn">Confirmer suppression</button>
            </form>
        </div>
    </div>

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

                if (modalId === 'item-edit-modal') {
                    document.getElementById('item-edit-form').action = `{{ url('service-modules/'.$moduleSlug.'/items') }}/${button.dataset.itemId}`;
                    document.getElementById('item-edit-name').value = button.dataset.itemName ?? '';
                    document.getElementById('item-edit-phone').value = button.dataset.itemPhone ?? '';
                    document.getElementById('item-edit-price').value = button.dataset.itemPrice ?? '';
                    document.getElementById('item-edit-status').value = button.dataset.itemStatus ?? 'active';
                    document.getElementById('item-edit-notes').value = button.dataset.itemNotes ?? '';
                }

                if (modalId === 'item-delete-modal') {
                    document.getElementById('item-delete-form').action = `{{ url('service-modules/'.$moduleSlug.'/items') }}/${button.dataset.itemId}`;
                    document.getElementById('item-delete-text').textContent = `Confirmer la suppression de "${button.dataset.itemName}" ?`;
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
