@extends('layouts.app')

@section('content')
    @php
        $canUpdateReservation = auth()->user()?->canFeature('reservations', 'update', 'update') ?? false;
        $serviceEntreesRows = $reservation->serviceEntrees
            ->where('is_deleted', false)
            ->sortByDesc(function ($entree) {
                return $entree->created_dtm ?? $entree->created_at;
            })
            ->values();
        $totalEntreeQuantity = (int) $serviceEntreesRows->sum('quantite');
        $totalSortieQuantity = (int) $serviceEntreesRows->sum(function ($entree) {
            return (int) $entree->retours->sum('quantite_retournee');
        });
        $remainingGlobalQuantity = max($totalEntreeQuantity - $totalSortieQuantity, 0);
        $isInventoryClosed = $serviceEntreesRows->isNotEmpty() && $remainingGlobalQuantity === 0;
        $defaultWizardStep = old('quantite_retournee') ? 2 : 1;
        if ($errors->has('quantite_add') || $errors->has('history_note')) {
            $defaultWizardStep = 1;
        }
        if ($errors->has('service_retour') || $errors->has('quantite_retournee') || $errors->has('note_retour')) {
            $defaultWizardStep = 2;
        }
        if ($isInventoryClosed) {
            $defaultWizardStep = 3;
        }
        $openIncrementEntreeId = (int) old('entree_id', 0);
        $openSortieEntreeId = (int) old('entree_id_sortie', 0);
    @endphp

    <style>
        .reservation-flash {
            border: 1px solid #d4e7d8;
            border-radius: 12px;
            padding: 10px 12px;
            background: #effaf1;
            color: #1a5b2a;
            font-size: 13px;
            font-weight: 600;
        }

        .reservation-error-box {
            border: 1px solid #f1c6c2;
            border-radius: 12px;
            padding: 10px 12px;
            background: #fff1f0;
            color: #9c2f2a;
            font-size: 13px;
        }

        .reservation-error-box ul {
            margin: 6px 0 0 18px;
            padding: 0;
        }

        .service-inventory-page {
            display: grid;
            gap: 14px;
        }

        .service-inventory-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .service-inventory-title {
            margin: 0;
            color: #14314d;
            font-size: 24px;
            font-weight: 800;
        }

        .service-inventory-sub {
            margin: 4px 0 0;
            color: #55708c;
            font-size: 13px;
        }

        .service-inventory-card {
            border: 1px solid #dbe7f4;
            border-radius: 14px;
            background: #ffffff;
            box-shadow: 0 12px 26px rgba(20, 49, 77, 0.08);
            padding: 12px;
            display: grid;
            gap: 10px;
        }

        .reservation-actions-row {
            display: flex;
            justify-content: flex-end;
        }

        .payment-form {
            border: 1px dashed #c9dbef;
            border-radius: 12px;
            padding: 10px;
            display: grid;
            gap: 8px;
            background: #f9fcff;
        }

        .payment-form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .payment-form label {
            display: block;
            margin-bottom: 4px;
            color: #4f6c88;
            font-size: 12px;
            font-weight: 700;
        }

        .payment-form input,
        .payment-form select {
            width: 100%;
            border: 1px solid #ccdbeb;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 13px;
            background: #fff;
        }

        .reservation-empty {
            color: #5f7690;
            font-size: 13px;
            margin: 0;
        }

        .additional-service-category {
            border: 1px solid #dbe8f5;
            border-radius: 11px;
            background: #f8fbff;
            padding: 8px;
            display: grid;
            gap: 6px;
        }

        .additional-service-category h4 {
            margin: 0;
            font-size: 13px;
            color: #1f4970;
        }

        .reservation-kv {
            border: 1px solid #e5edf7;
            border-radius: 10px;
            padding: 9px 10px;
            background: #fcfdff;
            display: grid;
            gap: 3px;
        }

        .reservation-kv-key {
            color: #59728b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-weight: 700;
        }

        .reservation-kv-value {
            color: #1a3a57;
            font-size: 14px;
            font-weight: 600;
        }

        .salle-option {
            border: 1px solid #d5e2f0;
            border-radius: 10px;
            padding: 8px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            background: #fcfdff;
        }

        .salle-option small {
            color: #607a95;
            font-size: 12px;
        }

        .inventory-wizard-steps {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .inventory-wizard-step {
            border: 1px solid #d6e5f4;
            border-radius: 12px;
            background: #f4f9ff;
            padding: 10px;
            text-align: left;
            cursor: pointer;
            color: #315474;
            transition: all .15s ease;
        }

        .inventory-wizard-step strong {
            display: block;
            font-size: 13px;
        }

        .inventory-wizard-step small {
            display: block;
            margin-top: 2px;
            font-size: 11px;
            color: #607a95;
        }

        .inventory-wizard-step.is-active {
            border-color: #2c78b6;
            background: #eaf4ff;
            box-shadow: 0 0 0 2px rgba(44, 120, 182, 0.16);
        }

        .inventory-wizard-step.is-done {
            border-color: #8fc7a0;
            background: #edfaf1;
        }

        .inventory-wizard-panel {
            display: none;
            gap: 10px;
        }

        .inventory-wizard-panel.is-active {
            display: grid;
        }

        .inventory-wizard-title {
            margin: 0;
            color: #14314d;
            font-size: 15px;
            font-weight: 800;
        }

        .inventory-summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        .inventory-status-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid #c6def9;
            background: #eaf4ff;
            color: #1e5f9d;
        }

        .inventory-status-pill.ok {
            border-color: #bce8ce;
            background: #e7f7ee;
            color: #1d6a3d;
        }

        .inventory-history-list {
            display: grid;
            gap: 6px;
            margin-top: 6px;
        }

        .inventory-history-item {
            border: 1px solid #d7e4f2;
            border-radius: 9px;
            background: #fff;
            padding: 7px 8px;
            display: grid;
            gap: 2px;
            font-size: 12px;
            color: #345675;
        }

        .inventory-table-wrap {
            overflow-x: auto;
            border: 1px solid #dbe7f4;
            border-radius: 12px;
            background: #fff;
        }

        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 780px;
        }

        .inventory-table th,
        .inventory-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #e6eff9;
            text-align: left;
            vertical-align: top;
            font-size: 12px;
            color: #1f3f5d;
        }

        .inventory-table th {
            background: #f5faff;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-size: 11px;
            color: #52708b;
        }

        .inventory-table tr:last-child td {
            border-bottom: 0;
        }

        .inventory-table-actions {
            white-space: nowrap;
        }

        .inventory-inline-form-row {
            display: none;
            background: #fbfdff;
        }

        .inventory-inline-form-row.is-open {
            display: table-row;
        }

        .inventory-reste-input {
            width: 110px;
            border: 1px solid #ccdbeb;
            border-radius: 8px;
            padding: 6px 8px;
            font-size: 12px;
        }

        .inventory-reste-input:disabled {
            background: #eef2f7;
            color: #7a8794;
            cursor: not-allowed;
        }

        .inventory-inline-error {
            margin-top: 6px;
            font-size: 12px;
            color: #9c2f2a;
            font-weight: 700;
        }

        @media (max-width: 860px) {
            .payment-form-row {
                grid-template-columns: 1fr;
            }

            .inventory-wizard-steps,
            .inventory-summary-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <section class="service-inventory-page">
        @if (session('success'))
            <div class="reservation-flash">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="reservation-error-box">
                <strong>Verifie les informations:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="service-inventory-header">
            <div>
                <h1 class="service-inventory-title">Entrees / sorties services</h1>
                <p class="service-inventory-sub">Reservation: {{ $reservation->title ?: ('Reservation #' . $reservation->id) }}</p>
            </div>
            <a href="{{ route('reservations.show', $reservation) }}" class="btn">Retour reservation</a>
        </div>

        <article class="service-inventory-card">
            <div class="inventory-wizard-steps" id="inventory-wizard-steps">
                <button type="button" class="inventory-wizard-step" data-step="1">
                    <strong>1. Entree</strong>
                    <small>Ajout des produits</small>
                </button>
                <button type="button" class="inventory-wizard-step" data-step="2">
                    <strong>2. Sortie</strong>
                    <small>Consommation / sorties</small>
                </button>
                <button type="button" class="inventory-wizard-step" data-step="3">
                    <strong>3. Cloture</strong>
                    <small>Synthese finale</small>
                </button>
            </div>

            <section class="inventory-wizard-panel" data-step-panel="1">
                <h2 class="inventory-wizard-title">Etape 1: Entree</h2>

                @if ($canUpdateReservation && ($reservation->status ?? null) !== 'cancelled')
                    <form method="POST" action="{{ route('reservations.service-entrees.store', $reservation) }}" class="payment-form">
                        @csrf
                        <div class="payment-form-row">
                            <div>
                                <label for="entree-nature">Produit / nature</label>
                                <input id="entree-nature" name="nature" type="text" required value="{{ old('nature') }}" placeholder="Ex: Jus, Eau, Cafe...">
                            </div>
                            <div>
                                <label for="entree-quantite">Quantite entree</label>
                                <input id="entree-quantite" name="quantite" type="number" min="1" required value="{{ old('quantite') }}">
                            </div>
                            <div>
                                <label for="entree-moment-service">Moment service</label>
                                <select id="entree-moment-service" name="moment_service">
                                    <option value=""></option>
                                    <option value="debut" {{ old('moment_service') === 'debut' ? 'selected' : '' }}>Debut</option>
                                    <option value="diner" {{ old('moment_service') === 'diner' ? 'selected' : '' }}>Diner</option>
                                    <option value="milieu" {{ old('moment_service') === 'milieu' ? 'selected' : '' }}>Milieu</option>
                                    <option value="fin" {{ old('moment_service') === 'fin' ? 'selected' : '' }}>Fin</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="entree-note">Note</label>
                            <input id="entree-note" name="note" type="text" value="{{ old('note') }}" placeholder="Optionnel">
                        </div>
                        <div class="reservation-actions-row">
                            <button type="submit" class="btn btn-primary">Ajouter entree</button>
                        </div>
                    </form>
                @endif

                @if ($serviceEntreesRows->isEmpty())
                    <p class="reservation-empty">Aucune entree enregistree.</p>
                @else
                    <div class="inventory-table-wrap">
                        <table class="inventory-table">
                            <thead>
                                <tr>
                                    <th>Produit / nature</th>
                                    <th>Quantite entree</th>
                                    <th>Moment service</th>
                                    <th>Cree le</th>
                                    <th>Cree par</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($serviceEntreesRows as $entree)
                                    <tr>
                                        <td>{{ $entree->nature }}</td>
                                        <td><strong>{{ (int) $entree->quantite }}</strong></td>
                                        <td>{{ $entree->moment_service ?: '-' }}</td>
                                        <td>
                                            @if ($entree->created_dtm)
                                                @frDateTime($entree->created_dtm)
                                            @else
                                                @frDateTime($entree->created_at)
                                            @endif
                                        </td>
                                        <td>{{ $entree->creator?->name ?? '-' }}</td>
                                        <td class="inventory-table-actions">
                                            @if ($canUpdateReservation && ($reservation->status ?? null) !== 'cancelled')
                                                <button type="button" class="btn js-toggle-increment-form" data-entree-id="{{ $entree->id }}">Modifier quantite</button>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr class="inventory-inline-form-row" data-form-for-entree="{{ $entree->id }}">
                                        <td colspan="6">
                                            @if ($canUpdateReservation && ($reservation->status ?? null) !== 'cancelled')
                                                <form method="POST" action="{{ route('reservations.service-entrees.increment', [$reservation, $entree]) }}" class="payment-form" style="margin-top:4px;">
                                                    @csrf
                                                    <input type="hidden" name="entree_id" value="{{ $entree->id }}">
                                                    <div class="payment-form-row">
                                                        <div>
                                                            <label>Ajouter a la quantite existante</label>
                                                            <input type="number" name="quantite_add" min="1" required placeholder="Ex: 5">
                                                        </div>
                                                        <div>
                                                            <label>Note changement</label>
                                                            <input type="text" name="history_note" placeholder="Optionnel (raison, correction...)">
                                                        </div>
                                                    </div>
                                                    <div class="reservation-actions-row">
                                                        <button type="submit" class="btn">Ajouter quantite</button>
                                                    </div>
                                                </form>
                                            @endif

                                            <div class="inventory-history-list">
                                                @forelse ($entree->histories->sortByDesc(function ($historyRow) { return $historyRow->created_dtm ?? $historyRow->created_at; }) as $historyRow)
                                                    <div class="inventory-history-item">
                                                        <strong>
                                                            {{ $historyRow->action === 'create' ? 'Creation ligne' : 'Augmentation quantite' }}:
                                                            {{ (int) $historyRow->previous_quantite }} + {{ (int) $historyRow->delta_quantite }} = {{ (int) $historyRow->new_quantite }}
                                                        </strong>
                                                        <span>
                                                            @if ($historyRow->created_dtm)
                                                                @frDateTime($historyRow->created_dtm)
                                                            @else
                                                                @frDateTime($historyRow->created_at)
                                                            @endif
                                                            | Par: {{ $historyRow->creator?->name ?? '-' }}
                                                        </span>
                                                        @if (! empty($historyRow->note))
                                                            <span>Note: {{ $historyRow->note }}</span>
                                                        @endif
                                                    </div>
                                                @empty
                                                    <p class="reservation-empty">Aucun changement enregistre.</p>
                                                @endforelse
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="inventory-wizard-panel" data-step-panel="2">
                <h2 class="inventory-wizard-title">Etape 2: Sortie</h2>

                @if ($serviceEntreesRows->isEmpty())
                    <p class="reservation-empty">Ajoute d abord au moins une entree avant de passer aux sorties.</p>
                @else
                    <div class="inventory-table-wrap">
                        <table class="inventory-table">
                            <thead>
                                <tr>
                                    <th>QTE produit</th>
                                    <th>Produit</th>
                                    <th>Reste</th>
                                    <th>Valider</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($serviceEntreesRows as $entree)
                                    @php
                                        $totalRetourne = (int) $entree->retours->sum('quantite_retournee');
                                        $reste = max(((int) $entree->quantite) - $totalRetourne, 0);
                                    @endphp
                                    <tr>
                                        <td><strong>{{ (int) $entree->quantite }}</strong></td>
                                        <td>{{ $entree->nature }}</td>
                                        <td>
                                            @if ($reste > 0 && $canUpdateReservation && ($reservation->status ?? null) !== 'cancelled')
                                                <input type="number" class="inventory-reste-input js-sortie-qty" data-sortie-entree-id="{{ $entree->id }}" min="1" max="{{ (int) $entree->quantite }}" data-max-reste="{{ $reste }}" value="" placeholder="Max {{ $reste }}">
                                                <small style="display:block;color:#607a95;">Max entree: {{ (int) $entree->quantite }}</small>
                                            @else
                                                <strong>{{ $reste }}</strong>
                                            @endif
                                        </td>
                                        <td class="inventory-table-actions">
                                            @if ($reste > 0 && $canUpdateReservation && ($reservation->status ?? null) !== 'cancelled')
                                                <button type="button" class="btn js-toggle-sortie-form" data-sortie-entree-id="{{ $entree->id }}">Valider sortie</button>
                                            @else
                                                <span class="inventory-status-pill ok">Valide</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr class="inventory-inline-form-row" data-form-for-sortie-entree="{{ $entree->id }}">
                                        <td colspan="4">
                                            @if ($reste > 0 && $canUpdateReservation && ($reservation->status ?? null) !== 'cancelled')
                                                <form method="POST" action="{{ route('reservations.service-retours.store', [$reservation, $entree]) }}" class="payment-form js-sortie-form" style="margin-top:6px;" data-sortie-entree-id="{{ $entree->id }}" data-entree-quantite="{{ (int) $entree->quantite }}">
                                                    @csrf
                                                    <input type="hidden" name="entree_id_sortie" value="{{ $entree->id }}">
                                                    <input type="hidden" name="quantite_retournee" class="js-sortie-hidden-qty" value="">
                                                    <div class="payment-form-row">
                                                        <div>
                                                            <label>Note sortie</label>
                                                            <input type="text" name="note_retour" placeholder="Optionnel">
                                                        </div>
                                                        <div class="inventory-inline-error js-sortie-error" style="display:none;"></div>
                                                    </div>
                                                    <div class="reservation-actions-row">
                                                        <button type="submit" class="btn">Enregistrer sortie</button>
                                                    </div>
                                                </form>
                                            @endif

                                            <div class="inventory-history-list" data-sortie-history-list="{{ $entree->id }}">
                                                @if ($entree->retours->isNotEmpty())
                                                    @foreach ($entree->retours->sortByDesc(function ($retour) { return $retour->created_dtm ?? $retour->created_at; }) as $retour)
                                                        <div class="inventory-history-item">
                                                            <strong>Sortie: {{ (int) $retour->quantite_retournee }}</strong>
                                                            <span>
                                                                @if ($retour->created_dtm)
                                                                    @frDateTime($retour->created_dtm)
                                                                @else
                                                                    @frDateTime($retour->created_at)
                                                                @endif
                                                                | Par: {{ $retour->creator?->name ?? '-' }}
                                                            </span>
                                                            @if (! empty($retour->note_retour))
                                                                <span>Note: {{ $retour->note_retour }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                @else
                                                    <p class="reservation-empty">Aucune sortie enregistree.</p>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </section>

            <section class="inventory-wizard-panel" data-step-panel="3">
                <h2 class="inventory-wizard-title">Etape 3: Cloture</h2>

                <div class="inventory-summary-grid">
                    <div class="reservation-kv">
                        <span class="reservation-kv-key">Total entrees</span>
                        <span class="reservation-kv-value">{{ $totalEntreeQuantity }}</span>
                    </div>
                    <div class="reservation-kv">
                        <span class="reservation-kv-key">Total sorties</span>
                        <span class="reservation-kv-value">{{ $totalSortieQuantity }}</span>
                    </div>
                    <div class="reservation-kv">
                        <span class="reservation-kv-key">Reste global</span>
                        <span class="reservation-kv-value">{{ $remainingGlobalQuantity }}</span>
                    </div>
                </div>

                <div>
                    <span class="inventory-status-pill {{ $isInventoryClosed ? 'ok' : '' }}">
                        {{ $isInventoryClosed ? 'Cloturee' : 'Non cloturee' }}
                    </span>
                </div>

                @if ($isInventoryClosed)
                    <p class="reservation-empty">Inventaire cloture: toutes les quantites saisies en entree ont ete sorties.</p>
                @else
                    <p class="reservation-empty">La cloture sera effective quand le reste global atteindra 0.</p>
                @endif

                <div class="reservation-actions-row" style="justify-content:flex-start;">
                    <a href="{{ route('reservations.show', $reservation) }}" class="btn">Retour reservation</a>
                </div>
            </section>
        </article>
    </section>

    <script>
        (function () {
            const defaultStep = Number("{{ $defaultWizardStep }}") || 1;
            const stepButtons = Array.from(document.querySelectorAll('#inventory-wizard-steps .inventory-wizard-step'));
            const stepPanels = Array.from(document.querySelectorAll('.inventory-wizard-panel'));

            const activateStep = (step) => {
                const normalizedStep = Math.min(3, Math.max(1, Number(step) || 1));

                stepButtons.forEach((btn) => {
                    const btnStep = Number(btn.dataset.step || 1);
                    btn.classList.toggle('is-active', btnStep === normalizedStep);
                    btn.classList.toggle('is-done', btnStep < normalizedStep);
                });

                stepPanels.forEach((panel) => {
                    const panelStep = Number(panel.getAttribute('data-step-panel') || 1);
                    panel.classList.toggle('is-active', panelStep === normalizedStep);
                });
            };

            stepButtons.forEach((btn) => {
                btn.addEventListener('click', () => activateStep(Number(btn.dataset.step || 1)));
            });

            const toggleIncrementButtons = Array.from(document.querySelectorAll('.js-toggle-increment-form'));
            const incrementInlineRows = Array.from(document.querySelectorAll('.inventory-inline-form-row[data-form-for-entree]'));

            const closeAllIncrementInlineForms = () => {
                incrementInlineRows.forEach((row) => row.classList.remove('is-open'));
            };

            toggleIncrementButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const entryId = String(btn.getAttribute('data-entree-id') || '');
                    const targetRow = document.querySelector(`.inventory-inline-form-row[data-form-for-entree="${entryId}"]`);
                    if (!targetRow) {
                        return;
                    }

                    const wasOpen = targetRow.classList.contains('is-open');
                    closeAllIncrementInlineForms();
                    if (!wasOpen) {
                        targetRow.classList.add('is-open');
                    }
                });
            });

            const openEntreeId = Number("{{ $openIncrementEntreeId }}") || 0;
            if (openEntreeId > 0) {
                const row = document.querySelector(`.inventory-inline-form-row[data-form-for-entree="${openEntreeId}"]`);
                if (row) {
                    row.classList.add('is-open');
                }
            }

            const sortieToggleButtons = Array.from(document.querySelectorAll('.js-toggle-sortie-form'));
            const sortieInlineRows = Array.from(document.querySelectorAll('.inventory-inline-form-row[data-form-for-sortie-entree]'));

            const closeAllSortieInlineForms = () => {
                sortieInlineRows.forEach((row) => row.classList.remove('is-open'));
            };

            sortieToggleButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const entryId = String(btn.getAttribute('data-sortie-entree-id') || '');
                    const targetRow = document.querySelector(`.inventory-inline-form-row[data-form-for-sortie-entree="${entryId}"]`);
                    if (!targetRow) {
                        return;
                    }

                    const wasOpen = targetRow.classList.contains('is-open');
                    closeAllSortieInlineForms();
                    if (!wasOpen) {
                        targetRow.classList.add('is-open');
                    }
                });
            });

            const openSortieId = Number("{{ $openSortieEntreeId }}") || 0;
            if (openSortieId > 0) {
                const row = document.querySelector(`.inventory-inline-form-row[data-form-for-sortie-entree="${openSortieId}"]`);
                if (row) {
                    row.classList.add('is-open');
                }
            }

            const sortieForms = Array.from(document.querySelectorAll('.js-sortie-form'));
            sortieForms.forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const entryId = String(form.getAttribute('data-sortie-entree-id') || '');
                    const qtyInput = document.querySelector(`.js-sortie-qty[data-sortie-entree-id="${entryId}"]`);
                    const hiddenQtyInput = form.querySelector('.js-sortie-hidden-qty');
                    const noteInput = form.querySelector('input[name="note_retour"]');
                    const submitButton = form.querySelector('button[type="submit"]');
                    const errorBox = form.querySelector('.js-sortie-error');
                    const actionButton = document.querySelector(`.js-toggle-sortie-form[data-sortie-entree-id="${entryId}"]`);
                    const actionCell = actionButton ? actionButton.parentElement : null;
                    const historyList = document.querySelector(`.inventory-history-list[data-sortie-history-list="${entryId}"]`);

                    if (!qtyInput || !hiddenQtyInput || !submitButton) {
                        return;
                    }

                    const desiredQty = Number(qtyInput.value || 0);
                    const maxEntree = Number(form.getAttribute('data-entree-quantite') || 0);
                    const maxReste = Number(qtyInput.getAttribute('data-max-reste') || 0);

                    if (!Number.isFinite(desiredQty) || desiredQty < 1) {
                        if (errorBox) {
                            errorBox.textContent = 'Quantite invalide.';
                            errorBox.style.display = 'block';
                        }
                        return;
                    }

                    if (desiredQty > maxEntree) {
                        if (errorBox) {
                            errorBox.textContent = `La quantite ne peut pas depasser la quantite entree (${maxEntree}).`;
                            errorBox.style.display = 'block';
                        }
                        return;
                    }

                    if (desiredQty > maxReste) {
                        if (errorBox) {
                            errorBox.textContent = `La quantite ne peut pas depasser le reste actuel (${maxReste}).`;
                            errorBox.style.display = 'block';
                        }
                        return;
                    }

                    hiddenQtyInput.value = String(desiredQty);
                    if (errorBox) {
                        errorBox.style.display = 'none';
                        errorBox.textContent = '';
                    }

                    if (!window.confirm('Confirmer cet enregistrement de sortie ?')) {
                        return;
                    }

                    const formData = new FormData(form);
                    submitButton.disabled = true;

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formData,
                        });

                        const payload = await response.json();
                        if (!response.ok) {
                            if (errorBox) {
                                errorBox.textContent = payload.message || 'Erreur lors de la validation de la sortie.';
                                errorBox.style.display = 'block';
                            }
                            return;
                        }

                        const remaining = Number(payload.remaining_quantity || 0);
                        qtyInput.value = String(desiredQty);
                        qtyInput.setAttribute('data-max-reste', String(remaining));
                        qtyInput.disabled = true;

                        if (actionCell) {
                            actionCell.innerHTML = '<span class="inventory-status-pill ok">Valide</span>';
                        }

                        const inlineRow = form.closest('.inventory-inline-form-row');
                        if (inlineRow) {
                            inlineRow.classList.remove('is-open');
                        }

                        form.style.display = 'none';

                        if (remaining > 0 && qtyInput.nextElementSibling && qtyInput.nextElementSibling.tagName === 'SMALL') {
                            qtyInput.nextElementSibling.textContent = `Reste apres validation: ${remaining}`;
                        }

                        window.alert(payload.message || 'Sortie enregistree avec succes.');

                        if (historyList) {
                            const emptyNode = historyList.querySelector('.reservation-empty');
                            if (emptyNode) {
                                emptyNode.remove();
                            }

                            const dt = new Date(payload.retour?.created_at_iso || Date.now());
                            const dtLabel = Number.isNaN(dt.getTime())
                                ? ''
                                : dt.toLocaleString('fr-FR', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });

                            const item = document.createElement('div');
                            item.className = 'inventory-history-item';
                            const safeNote = payload.retour?.note_retour ? `<span>Note: ${payload.retour.note_retour}</span>` : '';
                            item.innerHTML = `<strong>Sortie: ${Number(payload.retour?.quantite_retournee || desiredQty)}</strong><span>${dtLabel} | Par: ${payload.retour?.creator_name || '-'}</span>${safeNote}`;
                            historyList.prepend(item);
                        }

                        if (noteInput) {
                            noteInput.value = '';
                        }
                    } catch (error) {
                        if (errorBox) {
                            errorBox.textContent = 'Erreur reseau. Reessaie.';
                            errorBox.style.display = 'block';
                        }
                    } finally {
                        submitButton.disabled = false;
                    }
                });
            });

            activateStep(defaultStep);
        })();
    </script>
@endsection
