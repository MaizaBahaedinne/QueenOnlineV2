@extends('layouts.app')

@section('content')
    @php
        $canCreate = auth()->user()?->canFeature('reservations', 'create', 'create') ?? false;
        $canUpdate = auth()->user()?->canFeature('reservations', 'update', 'update') ?? false;
        $canDelete = auth()->user()?->canFeature('reservations', 'delete', 'delete') ?? false;
        $groupedMappings = $mappings->groupBy('source_table');
        $totalMappings = $mappings->count();
        $activeMappings = $mappings->where('is_active', true)->count();
        $inactiveMappings = $totalMappings - $activeMappings;
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
        .mapping-summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 14px; }
        .mapping-summary-card { background: linear-gradient(180deg, #f9fbfd 0%, #eef5fb 100%); border: 1px solid #d6e0ec; border-radius: 16px; padding: 14px 16px; }
        .mapping-summary-label { margin: 0; color: #55738f; font-size: 12px; text-transform: uppercase; letter-spacing: 0.04em; }
        .mapping-summary-value { margin: 6px 0 0; color: #17324f; font-size: 30px; font-weight: 700; }
        .mapping-summary-hint { margin: 6px 0 0; color: #5d7389; font-size: 13px; }
        .mapping-layout { display: grid; gap: 14px; margin-top: 16px; }
        .mapping-actions-bar { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; justify-content: space-between; margin-top: 14px; }
        .mapping-actions-group { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .mapping-search { width: min(360px, 100%); }
        .mapping-groups { display: grid; gap: 14px; }
        .mapping-table-group { border: 1px solid #d6e0ec; border-radius: 18px; background: #fdfefe; overflow: hidden; }
        .mapping-table-group[hidden] { display: none; }
        .mapping-table-group summary { list-style: none; cursor: pointer; padding: 16px 18px; display: flex; align-items: center; justify-content: space-between; gap: 12px; background: linear-gradient(180deg, #f8fbfd 0%, #eff6fb 100%); }
        .mapping-table-group summary::-webkit-details-marker { display: none; }
        .mapping-table-meta { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .mapping-table-name { margin: 0; color: #17324f; font-size: 18px; font-weight: 700; }
        .mapping-table-stats { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .mapping-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: 5px 10px; font-size: 12px; font-weight: 600; }
        .mapping-pill-neutral { background: #eaf1f8; color: #36536d; }
        .mapping-pill-success { background: #e4f5ea; color: #21663b; }
        .mapping-pill-muted { background: #f1f3f5; color: #5f6d7a; }
        .mapping-group-body { padding: 16px 18px 18px; }
        .mapping-card-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; }
        .mapping-card { border: 1px solid #d6e0ec; border-radius: 16px; padding: 14px; background: #fff; box-shadow: 0 8px 18px rgba(15, 44, 71, 0.05); }
        .mapping-card[hidden] { display: none; }
        .mapping-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
        .mapping-card-title { margin: 0; color: #17324f; font-size: 16px; font-weight: 700; }
        .mapping-card-sub { margin: 4px 0 0; color: #6a7f93; font-size: 13px; }
        .mapping-card-body { display: grid; gap: 10px; margin-top: 12px; }
        .mapping-data-row { display: grid; gap: 4px; padding: 10px 12px; border-radius: 12px; background: #f8fbfd; }
        .mapping-data-label { color: #62809b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
        .mapping-data-value { color: #17324f; font-size: 14px; line-height: 1.45; word-break: break-word; }
        .mapping-card-actions { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 12px; }
        .mapping-empty { border: 1px dashed #c8d6e4; border-radius: 16px; padding: 26px 18px; text-align: center; color: #60778c; background: #fafcfe; }
        .mapping-empty strong { display: block; color: #17324f; margin-bottom: 6px; }

        @media (max-width: 860px) {
            .mapping-form-grid { grid-template-columns: 1fr; }
            .mapping-summary-grid { grid-template-columns: 1fr; }
            .mapping-actions-bar { align-items: stretch; }
            .mapping-actions-group { width: 100%; }
            .mapping-search { width: 100%; }
            .mapping-table-group summary { padding: 14px; }
            .mapping-group-body { padding: 14px; }
        }
    </style>

    <section class="panel">
        <h1 class="panel-title">Mapping migration</h1>
        <p class="panel-sub">Saisie base legacy uniquement (table/colonne source), avec condition/valeur et signification.</p>

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

        <div class="mapping-summary-grid">
            <article class="mapping-summary-card">
                <p class="mapping-summary-label">Tables source</p>
                <p class="mapping-summary-value">{{ $groupedMappings->count() }}</p>
                <p class="mapping-summary-hint">Organisation par blocs repliables pour eviter le tableau large.</p>
            </article>
            <article class="mapping-summary-card">
                <p class="mapping-summary-label">Mappings actifs</p>
                <p class="mapping-summary-value">{{ $activeMappings }}</p>
                <p class="mapping-summary-hint">Sur {{ $totalMappings }} lignes, {{ $inactiveMappings }} sont inactives.</p>
            </article>
            <article class="mapping-summary-card">
                <p class="mapping-summary-label">Filtre courant</p>
                <p class="mapping-summary-value" style="font-size:22px;">{{ $sourceTableFilter !== '' ? $sourceTableFilter : 'Toutes les tables' }}</p>
                <p class="mapping-summary-hint">Ajoute une recherche texte pour retrouver une colonne ou une regle.</p>
            </article>
        </div>

        <div class="mapping-layout">
            <div class="mapping-actions-bar">
                <div class="mapping-actions-group">
                    <input
                        type="search"
                        id="mapping-search-input"
                        class="search mapping-search"
                        style="max-width:none;"
                        placeholder="Rechercher une colonne, une condition ou une signification"
                    >
                    <button type="button" class="btn" id="mapping-expand-all">Tout ouvrir</button>
                    <button type="button" class="btn" id="mapping-collapse-all">Tout fermer</button>
                </div>
                <div class="mapping-actions-group">
                    <span class="mapping-pill mapping-pill-neutral" id="mapping-visible-count">{{ $totalMappings }} ligne(s) visible(s)</span>
                </div>
            </div>

            @if ($groupedMappings->isEmpty())
                <div class="mapping-empty">
                    <strong>Aucune ligne de mapping.</strong>
                    Cree un premier mapping ou retire les filtres actifs.
                </div>
            @else
                <div class="mapping-groups" id="mapping-groups">
                    @foreach ($groupedMappings as $table => $rows)
                        @php
                            $activeCount = $rows->where('is_active', true)->count();
                            $openGroup = $sourceTableFilter !== '' || $loop->first;
                        @endphp
                        <details class="mapping-table-group" data-source-table="{{ \\Illuminate\\Support\\Str::lower($table) }}" {{ $openGroup ? 'open' : '' }}>
                            <summary>
                                <div>
                                    <h2 class="mapping-table-name">{{ $table }}</h2>
                                    <div class="mapping-table-meta">
                                        <span class="mapping-pill mapping-pill-neutral">{{ $rows->count() }} ligne(s)</span>
                                        <span class="mapping-pill mapping-pill-success">{{ $activeCount }} active(s)</span>
                                        @if ($rows->count() - $activeCount > 0)
                                            <span class="mapping-pill mapping-pill-muted">{{ $rows->count() - $activeCount }} inactive(s)</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="mapping-table-stats">
                                    <span class="mapping-pill mapping-pill-neutral">Table source</span>
                                </div>
                            </summary>
                            <div class="mapping-group-body">
                                <div class="mapping-card-grid">
                                    @foreach ($rows as $row)
                                        @php
                                            $searchText = implode(' ', array_filter([
                                                $row->source_table,
                                                $row->source_column,
                                                $row->condition_value,
                                                $row->signification,
                                                (string) $row->sort_order,
                                                $row->is_active ? 'oui active' : 'non inactive',
                                            ]));
                                        @endphp
                                        <article class="mapping-card" data-search-text="{{ \\Illuminate\\Support\\Str::lower($searchText) }}">
                                            <div class="mapping-card-top">
                                                <div>
                                                    <h3 class="mapping-card-title">{{ $row->source_column }}</h3>
                                                    <p class="mapping-card-sub">Cible: {{ $row->target_table ?: $row->source_table }}.{{ $row->target_column ?: $row->source_column }}</p>
                                                </div>
                                                <span class="mapping-pill {{ $row->is_active ? 'mapping-pill-success' : 'mapping-pill-muted' }}">{{ $row->is_active ? 'Actif' : 'Inactif' }}</span>
                                            </div>

                                            <div class="mapping-card-body">
                                                <div class="mapping-data-row">
                                                    <span class="mapping-data-label">Condition / Valeur</span>
                                                    <span class="mapping-data-value">{{ $row->condition_value ?: 'Aucune condition specifique' }}</span>
                                                </div>
                                                <div class="mapping-data-row">
                                                    <span class="mapping-data-label">Signification</span>
                                                    <span class="mapping-data-value">{{ $row->signification ?: 'Aucune signification renseignee' }}</span>
                                                </div>
                                                <div class="mapping-data-row">
                                                    <span class="mapping-data-label">Ordre</span>
                                                    <span class="mapping-data-value">{{ $row->sort_order }}</span>
                                                </div>
                                            </div>

                                            <div class="mapping-card-actions">
                                                @if ($canUpdate)
                                                    <button
                                                        type="button"
                                                        class="btn"
                                                        data-open-modal="mapping-edit-modal"
                                                        data-map-id="{{ $row->id }}"
                                                        data-map-source-table="{{ $row->source_table }}"
                                                        data-map-source-column="{{ $row->source_column }}"
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
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        </details>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    @if ($canCreate)
        <div class="modal-overlay" id="mapping-create-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Ajouter ligne de mapping</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" action="{{ route('migration-mappings.store') }}" style="display:grid; gap:10px;">@csrf
                <input type="hidden" name="source_connection" id="mapping-create-source-connection" value="{{ $defaultSourceConnection ?? 'legacy' }}">
                <input type="hidden" name="target_table" id="mapping-create-target-table" value="">
                <input type="hidden" name="target_column" id="mapping-create-target-column" value="">
                <div class="mapping-form-grid">
                    <div>
                        <label>Table source</label>
                        <select class="search" style="max-width:none;" name="source_table" id="mapping-create-source-table" required>
                            <option value="">Chargement...</option>
                        </select>
                    </div>
                    <div>
                        <label>Colonne source</label>
                        <select class="search" style="max-width:none;" name="source_column" id="mapping-create-source-column" required>
                            <option value="">Selectionner table d'abord</option>
                        </select>
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
                <input type="hidden" id="mapping-edit-source-connection" name="source_connection" value="{{ $defaultSourceConnection ?? 'legacy' }}">
                <input type="hidden" id="mapping-edit-target-table" name="target_table" value="">
                <input type="hidden" id="mapping-edit-target-column" name="target_column" value="">
                <div class="mapping-form-grid">
                    <div>
                        <label>Table source</label>
                        <select class="search" style="max-width:none;" id="mapping-edit-source-table" name="source_table" required>
                            <option value="">Chargement...</option>
                        </select>
                    </div>
                    <div>
                        <label>Colonne source</label>
                        <select class="search" style="max-width:none;" id="mapping-edit-source-column" name="source_column" required>
                            <option value="">Selectionner table d'abord</option>
                        </select>
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
        const schemaTablesUrl = "{{ route('migration-mappings.schema.tables') }}";
        const schemaColumnsUrl = "{{ route('migration-mappings.schema.columns') }}";
        const mappingSearchInput = document.getElementById('mapping-search-input');
        const mappingVisibleCount = document.getElementById('mapping-visible-count');
        const mappingGroups = Array.from(document.querySelectorAll('.mapping-table-group'));
        const mappingExpandAllButton = document.getElementById('mapping-expand-all');
        const mappingCollapseAllButton = document.getElementById('mapping-collapse-all');

        const updateVisibleCount = () => {
            if (!mappingVisibleCount) {
                return;
            }

            const visibleCards = document.querySelectorAll('.mapping-card:not([hidden])').length;
            mappingVisibleCount.textContent = `${visibleCards} ligne(s) visible(s)`;
        };

        const filterMappings = () => {
            const term = (mappingSearchInput?.value || '').trim().toLowerCase();

            mappingGroups.forEach((group) => {
                let hasVisibleCard = false;
                const cards = Array.from(group.querySelectorAll('.mapping-card'));

                cards.forEach((card) => {
                    const haystack = card.dataset.searchText || '';
                    const matches = term === '' || haystack.includes(term);
                    card.hidden = !matches;
                    if (matches) {
                        hasVisibleCard = true;
                    }
                });

                group.hidden = !hasVisibleCard;
                if (term !== '' && hasVisibleCard) {
                    group.open = true;
                }
            });

            updateVisibleCount();
        };

        mappingSearchInput?.addEventListener('input', filterMappings);
        mappingExpandAllButton?.addEventListener('click', () => {
            mappingGroups.forEach((group) => {
                if (!group.hidden) {
                    group.open = true;
                }
            });
        });
        mappingCollapseAllButton?.addEventListener('click', () => {
            mappingGroups.forEach((group) => {
                group.open = false;
            });
        });

        const fetchSchemaTables = async (connection) => {
            const params = new URLSearchParams({ connection });
            const response = await fetch(`${schemaTablesUrl}?${params.toString()}`, { headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload?.message || 'Impossible de charger les tables.');
            }
            return payload.tables || [];
        };

        const fetchSchemaColumns = async (connection, table) => {
            const params = new URLSearchParams({ connection, table });
            const response = await fetch(`${schemaColumnsUrl}?${params.toString()}`, { headers: { Accept: 'application/json' } });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload?.message || 'Impossible de charger les colonnes.');
            }
            return payload.columns || [];
        };

        const fillSelect = (select, values, { placeholder, selectedValue = '', allowEmpty = true } = {}) => {
            if (!select) return;

            select.innerHTML = '';
            if (allowEmpty) {
                const placeholderOption = document.createElement('option');
                placeholderOption.value = '';
                placeholderOption.textContent = placeholder;
                select.appendChild(placeholderOption);
            }

            values.forEach((value) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = value;
                if (selectedValue && value === selectedValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            });

            if (!selectedValue && !allowEmpty && values.length > 0) {
                select.value = values[0];
            }
        };

        const wireSchemaSelectors = (prefix) => {
            const sourceConnection = document.getElementById(`${prefix}-source-connection`);
            const sourceTable = document.getElementById(`${prefix}-source-table`);
            const sourceColumn = document.getElementById(`${prefix}-source-column`);
            const targetTable = document.getElementById(`${prefix}-target-table`);
            const targetColumn = document.getElementById(`${prefix}-target-column`);

            if (!sourceConnection || !sourceTable || !sourceColumn || !targetTable || !targetColumn) {
                return {
                    syncWithValues: async () => {},
                };
            }

            const syncTargetFields = () => {
                targetTable.value = sourceTable.value || '';
                targetColumn.value = sourceColumn.value || '';
            };

            const loadSourceTables = async (selected = '') => {
                const tables = await fetchSchemaTables(sourceConnection.value);
                fillSelect(sourceTable, tables, {
                    placeholder: 'Selectionner table source',
                    selectedValue: selected,
                    allowEmpty: false,
                });
                fillSelect(sourceColumn, [], {
                    placeholder: 'Selectionner colonne source',
                    allowEmpty: false,
                });
                syncTargetFields();
            };

            const loadSourceColumns = async (selected = '') => {
                if (!sourceTable.value) {
                    fillSelect(sourceColumn, [], {
                        placeholder: 'Selectionner colonne source',
                        allowEmpty: false,
                    });
                    syncTargetFields();
                    return;
                }
                const columns = await fetchSchemaColumns(sourceConnection.value, sourceTable.value);
                fillSelect(sourceColumn, columns, {
                    placeholder: 'Selectionner colonne source',
                    selectedValue: selected,
                    allowEmpty: false,
                });
                syncTargetFields();
            };

            sourceConnection.addEventListener('change', () => {
                loadSourceTables()
                    .then(() => loadSourceColumns())
                    .catch(() => {
                        fillSelect(sourceTable, [], {
                            placeholder: 'Selectionner table source',
                            allowEmpty: false,
                        });
                    });
            });
            sourceTable.addEventListener('change', () => {
                loadSourceColumns().catch(() => {
                    fillSelect(sourceColumn, [], {
                        placeholder: 'Selectionner colonne source',
                        allowEmpty: false,
                    });
                });
            });
            sourceColumn.addEventListener('change', syncTargetFields);

            return {
                syncWithValues: async ({ sourceTableValue = '', sourceColumnValue = '' } = {}) => {
                    await loadSourceTables(sourceTableValue);
                    await loadSourceColumns(sourceColumnValue);
                    syncTargetFields();
                },
            };
        };

        const createSelectors = wireSchemaSelectors('mapping-create');
        const editSelectors = wireSchemaSelectors('mapping-edit');

    filterMappings();
        createSelectors.syncWithValues().catch(() => {});

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
                    editSelectors.syncWithValues({
                        sourceTableValue: button.dataset.mapSourceTable ?? '',
                        sourceColumnValue: button.dataset.mapSourceColumn ?? '',
                    }).catch(() => {});
                    document.getElementById('mapping-edit-condition-value').value = button.dataset.mapConditionValue ?? '';
                    document.getElementById('mapping-edit-signification').value = button.dataset.mapSignification ?? '';
                    document.getElementById('mapping-edit-sort-order').value = button.dataset.mapSortOrder ?? '0';
                    document.getElementById('mapping-edit-is-active').value = button.dataset.mapIsActive ?? '1';
                }

                if (modalId === 'mapping-create-modal') {
                    createSelectors.syncWithValues().catch(() => {});
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
