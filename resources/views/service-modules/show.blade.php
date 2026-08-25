@extends('layouts.app')

@section('content')
    @php
        $canCreate = auth()->user()?->canFeature($moduleSlug, 'create', 'create') ?? false;
        $canUpdate = auth()->user()?->canFeature($moduleSlug, 'update', 'update') ?? false;
        $canDelete = auth()->user()?->canFeature($moduleSlug, 'delete', 'delete') ?? false;
        $canViewPacks = $moduleMeta['packs'] ? (auth()->user()?->canFeature($moduleSlug, 'list-pack', 'view') ?? false) : false;
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
        <h1 class="panel-title">{{ $moduleMeta['name'] }}</h1>
        <p class="panel-sub">Module metier operationnel (donnees + CRUD).</p>

        @if ($moduleMeta['packs'] && $canViewPacks)
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
                    @if ($canCreate)
                        <button type="button" class="btn btn-primary" data-open-modal="item-create-modal">Ajouter element</button>
                    @endif
                </div>
                <div style="overflow-x:auto; margin-top:10px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Telephone</th>
                                <th>Prix base</th>
                                @if ($moduleSlug === 'chanteur')
                                    <th>Prix partenariat (troupes)</th>
                                @endif
                                <th>Statut</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                @php
                                    $rowPartnership = $moduleSlug === 'chanteur'
                                        ? ($partnershipPricesBySinger[$item->id] ?? [])
                                        : [];
                                @endphp
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->phone ?? '-' }}</td>
                                    <td>{{ number_format((float) $item->base_price, 2, '.', ' ') }}</td>
                                    @if ($moduleSlug === 'chanteur')
                                        <td>
                                            @if (!empty($rowPartnership))
                                                @foreach ($troupes as $troupe)
                                                    @if (array_key_exists((string) $troupe->id, $rowPartnership))
                                                        <div style="font-size:12px;">
                                                            {{ $troupe->name }}: {{ number_format((float) $rowPartnership[(string) $troupe->id], 2, '.', ' ') }}
                                                        </div>
                                                    @endif
                                                @endforeach
                                            @else
                                                -
                                            @endif
                                        </td>
                                    @endif
                                    <td>{{ $item->status }}</td>
                                    <td>{{ $item->notes ?? '-' }}</td>
                                    <td>
                                        <div class="action-row">
                                            @if ($moduleSlug === 'chanteur' && $canUpdate)
                                                <button
                                                    type="button"
                                                    class="btn"
                                                    data-open-modal="item-partnership-modal"
                                                    data-item-id="{{ $item->id }}"
                                                    data-item-name="{{ $item->name }}"
                                                    data-item-partnership='@json($rowPartnership)'
                                                >
                                                    Prix partenariat
                                                </button>
                                            @endif
                                            @if ($canUpdate)
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
                                            @endif
                                            @if ($canDelete)
                                                <button
                                                    type="button"
                                                    class="btn"
                                                    data-open-modal="item-delete-modal"
                                                    data-item-id="{{ $item->id }}"
                                                    data-item-name="{{ $item->name }}"
                                                >
                                                    Supprimer
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $moduleSlug === 'chanteur' ? 7 : 6 }}" class="muted">Aucun element.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>

    @if ($canCreate)
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
    @endif

    @if ($canUpdate)
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
    @endif

    @if ($canDelete)
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
    @endif

    @if ($moduleSlug === 'chanteur' && $canUpdate)
        <div class="modal-overlay" id="item-partnership-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Prix de partenariat par troupe</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>
                <p class="panel-sub" id="item-partnership-subtitle" style="margin-bottom:10px;"></p>
                <form method="POST" id="item-partnership-form" action="#" style="display:grid; gap:10px;">
                    @csrf
                    @method('PATCH')
                    @forelse ($troupes as $troupe)
                        <label style="display:grid; gap:4px;">
                            <span style="font-weight:600;">{{ $troupe->name }}</span>
                            <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="partnership_prices[{{ $troupe->id }}]" id="partnership-price-{{ $troupe->id }}" placeholder="Prix partenariat">
                        </label>
                    @empty
                        <p class="muted">Aucune troupe musicale active trouvee.</p>
                    @endforelse
                    <button type="submit" class="btn btn-primary">Enregistrer les prix</button>
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

                if (modalId === 'item-partnership-modal') {
                    const itemId = button.dataset.itemId;
                    const itemName = button.dataset.itemName || '';
                    const form = document.getElementById('item-partnership-form');
                    if (!form) {
                        return;
                    }
                    form.action = `{{ url('service-modules/'.$moduleSlug.'/items') }}/${itemId}/partnership-prices`;
                    document.getElementById('item-partnership-subtitle').textContent = `Chanteur: ${itemName}`;

                    const rawData = button.dataset.itemPartnership || '{}';
                    let prices = {};
                    try {
                        prices = JSON.parse(rawData);
                    } catch (e) {
                        prices = {};
                    }

                    const partnershipInputs = form.querySelectorAll('input[id^="partnership-price-"]');
                    partnershipInputs.forEach((input) => {
                        const troupeId = input.id.replace('partnership-price-', '');
                        input.value = prices[String(troupeId)] ?? '';
                    });
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
