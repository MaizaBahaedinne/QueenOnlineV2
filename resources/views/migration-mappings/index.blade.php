@extends('layouts.app')

@section('content')
    @php
        $canCreate = auth()->user()?->canFeature('modules', 'create', 'create') ?? false;
        $canUpdate = auth()->user()?->canFeature('modules', 'update', 'update') ?? false;
        $canDelete = auth()->user()?->canFeature('modules', 'delete', 'delete') ?? false;
    @endphp

    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 19, 26, 0.56); display: none; align-items: center; justify-content: center; padding: 18px; z-index: 80; }
        .modal-overlay.show { display: flex; }
        .modal-card { width: min(860px, 100%); max-height: 90vh; overflow: auto; background: #fff; border: 1px solid #d6e0ec; border-radius: 16px; padding: 16px; box-shadow: 0 14px 30px rgba(14, 39, 69, 0.16); }
        .modal-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
        .modal-title { margin: 0; font-size: 20px; color: #17324f; }
        .mapping-toolbar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; margin-top: 12px; }
        .mapping-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .mapping-form-grid .full { grid-column: 1 / -1; }
        .mapping-group-title { margin: 14px 0 8px; color: #184268; font-size: 14px; font-weight: 700; }

        @media (max-width: 860px) {
            .mapping-form-grid { grid-template-columns: 1fr; }
        }
    </style>

    <section class="panel">
        <h1 class="panel-title">Mapping migration</h1>
        <p class="panel-sub">Table par table, colonne par colonne, avec condition/valeur et signification.</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        @if ($errors->any())
            <div class="badge badge-danger" style="margin-top:10px; display:block; text-align:left;">
                <strong>Erreurs:</strong>
                <ul style="margin: 6px 0 0 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mapping-toolbar">
            <form method="GET" action="{{ route('migration-mappings.index') }}" style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <select class="search" name="source_table" style="max-width:none; min-width: 220px;">
                    <option value="">Toutes les tables source</option>
                    @foreach ($sourceTables as $table)
                        <option value="{{ $table }}" {{ $sourceTableFilter === $table ? 'selected' : '' }}>{{ $table }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn">Filtrer</button>
                <a href="{{ route('migration-mappings.index') }}" class="btn">Reset</a>
            </form>

            <a href="{{ route('migration-mappings.export', array_filter(['format' => 'csv', 'source_table' => $sourceTableFilter !== '' ? $sourceTableFilter : null])) }}" class="btn">Exporter CSV</a>
            <a href="{{ route('migration-mappings.export', array_filter(['format' => 'json', 'source_table' => $sourceTableFilter !== '' ? $sourceTableFilter : null])) }}" class="btn">Exporter JSON</a>

            @if ($canCreate)
                <button type="button" class="btn btn-primary" data-open-modal="mapping-create-modal">Ajouter ligne mapping</button>
            @endif
        </div>

        <div style="overflow-x:auto; margin-top:12px;">
            <table>
                <thead>
                    <tr>
                        <th>Table source</th>
                        <th>Colonne source</th>
                        <th>Table cible</th>
                        <th>Colonne cible</th>
                        <th>Condition / Valeur</th>
                        <th>Signification</th>
                        <th>Ordre</th>
                        <th>Actif</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php $lastTable = null; @endphp
                    @forelse ($mappings as $row)
                        @if ($lastTable !== $row->source_table)
                            <tr>
                                <td colspan="9" class="mapping-group-title">{{ $row->source_table }}</td>
                            </tr>
                            @php $lastTable = $row->source_table; @endphp
                        @endif
                        <tr>
                            <td>{{ $row->source_table }}</td>
                            <td>{{ $row->source_column }}</td>
                            <td>{{ $row->target_table }}</td>
                            <td>{{ $row->target_column }}</td>
                            <td>{{ $row->condition_value ?: '-' }}</td>
                            <td>{{ $row->signification ?: '-' }}</td>
                            <td>{{ $row->sort_order }}</td>
                            <td>{{ $row->is_active ? 'Oui' : 'Non' }}</td>
                            <td>
                                <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                    @if ($canUpdate)
                                        <button
                                            type="button"
                                            class="btn"
                                            data-open-modal="mapping-edit-modal"
                                            data-map-id="{{ $row->id }}"
                                            data-map-source-table="{{ $row->source_table }}"
                                            data-map-source-column="{{ $row->source_column }}"
                                            data-map-target-table="{{ $row->target_table }}"
                                            data-map-target-column="{{ $row->target_column }}"
                                            data-map-condition-value="{{ $row->condition_value }}"
                                            data-map-signification="{{ $row->signification }}"
                                            data-map-sort-order="{{ $row->sort_order }}"
                                            data-map-is-active="{{ $row->is_active ? '1' : '0' }}"
                                        >Modifier</button>
                                    @endif
                                    @if ($canDelete)
                                        <button
                                            type="button"
                                            class="btn"
                                            data-open-modal="mapping-delete-modal"
                                            data-map-id="{{ $row->id }}"
                                            data-map-source-table="{{ $row->source_table }}"
                                            data-map-source-column="{{ $row->source_column }}"
                                        >Supprimer</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="muted">Aucune ligne de mapping.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canCreate)
        <div class="modal-overlay" id="mapping-create-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Ajouter ligne de mapping</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" action="{{ route('migration-mappings.store') }}" style="display:grid; gap:10px;">@csrf
                <div class="mapping-form-grid">
                    <div>
                        <label>Table source</label>
                        <input class="search" style="max-width:none;" name="source_table" type="text" required>
                    </div>
                    <div>
                        <label>Colonne source</label>
                        <input class="search" style="max-width:none;" name="source_column" type="text" required>
                    </div>
                    <div>
                        <label>Table cible</label>
                        <input class="search" style="max-width:none;" name="target_table" type="text" required>
                    </div>
                    <div>
                        <label>Colonne cible</label>
                        <input class="search" style="max-width:none;" name="target_column" type="text" required>
                    </div>
                    <div>
                        <label>Condition / Valeur</label>
                        <input class="search" style="max-width:none;" name="condition_value" type="text" placeholder="Ex: roleId=4">
                    </div>
                    <div>
                        <label>Ordre</label>
                        <input class="search" style="max-width:none;" name="sort_order" type="number" min="0" value="0">
                    </div>
                    <div class="full">
                        <label>Signification</label>
                        <textarea class="search" style="max-width:none; min-height:72px;" name="signification" placeholder="Expliquer la regle metier de mapping"></textarea>
                    </div>
                    <div>
                        <label>Actif</label>
                        <select class="search" style="max-width:none;" name="is_active">
                            <option value="1" selected>Oui</option>
                            <option value="0">Non</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form>
        </div></div>
    @endif

    @if ($canUpdate)
        <div class="modal-overlay" id="mapping-edit-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Modifier ligne de mapping</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" id="mapping-edit-form" action="#" style="display:grid; gap:10px;">@csrf @method('PATCH')
                <div class="mapping-form-grid">
                    <div>
                        <label>Table source</label>
                        <input class="search" style="max-width:none;" id="mapping-edit-source-table" name="source_table" type="text" required>
                    </div>
                    <div>
                        <label>Colonne source</label>
                        <input class="search" style="max-width:none;" id="mapping-edit-source-column" name="source_column" type="text" required>
                    </div>
                    <div>
                        <label>Table cible</label>
                        <input class="search" style="max-width:none;" id="mapping-edit-target-table" name="target_table" type="text" required>
                    </div>
                    <div>
                        <label>Colonne cible</label>
                        <input class="search" style="max-width:none;" id="mapping-edit-target-column" name="target_column" type="text" required>
                    </div>
                    <div>
                        <label>Condition / Valeur</label>
                        <input class="search" style="max-width:none;" id="mapping-edit-condition-value" name="condition_value" type="text">
                    </div>
                    <div>
                        <label>Ordre</label>
                        <input class="search" style="max-width:none;" id="mapping-edit-sort-order" name="sort_order" type="number" min="0">
                    </div>
                    <div class="full">
                        <label>Signification</label>
                        <textarea class="search" style="max-width:none; min-height:72px;" id="mapping-edit-signification" name="signification"></textarea>
                    </div>
                    <div>
                        <label>Actif</label>
                        <select class="search" style="max-width:none;" id="mapping-edit-is-active" name="is_active">
                            <option value="1">Oui</option>
                            <option value="0">Non</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Mettre a jour</button>
            </form>
        </div></div>
    @endif

    @if ($canDelete)
        <div class="modal-overlay" id="mapping-delete-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Supprimer ligne mapping</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <p id="mapping-delete-text" class="panel-sub"></p>
            <form method="POST" id="mapping-delete-form" action="#">@csrf @method('DELETE')
                <button type="submit" class="btn">Confirmer suppression</button>
            </form>
        </div></div>
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

                if (modalId === 'mapping-edit-modal') {
                    const id = button.dataset.mapId;
                    document.getElementById('mapping-edit-form').action = `{{ url('migration-mappings') }}/${id}`;
                    document.getElementById('mapping-edit-source-table').value = button.dataset.mapSourceTable ?? '';
                    document.getElementById('mapping-edit-source-column').value = button.dataset.mapSourceColumn ?? '';
                    document.getElementById('mapping-edit-target-table').value = button.dataset.mapTargetTable ?? '';
                    document.getElementById('mapping-edit-target-column').value = button.dataset.mapTargetColumn ?? '';
                    document.getElementById('mapping-edit-condition-value').value = button.dataset.mapConditionValue ?? '';
                    document.getElementById('mapping-edit-signification').value = button.dataset.mapSignification ?? '';
                    document.getElementById('mapping-edit-sort-order').value = button.dataset.mapSortOrder ?? '0';
                    document.getElementById('mapping-edit-is-active').value = button.dataset.mapIsActive ?? '1';
                }

                if (modalId === 'mapping-delete-modal') {
                    const id = button.dataset.mapId;
                    const sourceTable = button.dataset.mapSourceTable ?? '';
                    const sourceColumn = button.dataset.mapSourceColumn ?? '';
                    document.getElementById('mapping-delete-form').action = `{{ url('migration-mappings') }}/${id}`;
                    document.getElementById('mapping-delete-text').textContent = `Confirmer la suppression du mapping ${sourceTable}.${sourceColumn} ?`;
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
