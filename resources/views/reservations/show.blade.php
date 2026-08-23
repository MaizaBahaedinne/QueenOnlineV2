@extends('layouts.app')

@section('content')
    @php
        $status = (string) ($reservation->status ?? 'pending');
        $statusLabel = match ($status) {
            'confirmed' => 'Confirmee',
            'cancelled' => 'Annulee',
            'completed' => 'Terminee',
            default => 'En attente',
        };
        $statusTone = match ($status) {
            'confirmed' => 'ok',
            'completed' => 'info',
            'cancelled' => 'danger',
            default => 'warn',
        };
        $clientFullName = trim((string) ($reservation->client?->first_name . ' ' . $reservation->client?->name));
        $clientFullName = $clientFullName !== '' ? $clientFullName : ($reservation->client?->name ?? '-');
        $canUpdateReservation = auth()->user()?->canFeature('reservations', 'update', 'update') ?? false;
        $canCreatePayment = auth()->user()?->canFeature('payments', 'create', 'create') ?? false;
        $totalAmount = (float) ($reservation->total_amount ?? 0);
        $totalPaid = (float) $reservation->payments->sum('amount');
        $remainingAmount = max($totalAmount - $totalPaid, 0);
        $paymentCount = $reservation->payments->count();
        $nextPhase = match (true) {
            $paymentCount === 0 => 'avance',
            $remainingAmount <= 0 => 'reste',
            $paymentCount === 1 => 'partie-1',
            $paymentCount === 2 => 'partie-2',
            $paymentCount === 3 => 'partie-3',
            default => 'reste',
        };
    @endphp

    <style>
        .reservation-show {
            --rs-bg-a: #f6fbff;
            --rs-bg-b: #eef6ff;
            --rs-card: #ffffff;
            --rs-line: #dbe7f4;
            --rs-text: #14314d;
            --rs-muted: #55708c;
            --rs-shadow: 0 14px 36px rgba(20, 49, 77, 0.10);
            display: grid;
            gap: 14px;
        }

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

        .reservation-show-hero {
            border: 1px solid #d5e3f2;
            border-radius: 18px;
            padding: 18px;
            background:
                radial-gradient(circle at 10% 15%, rgba(64, 162, 230, 0.20) 0%, rgba(64, 162, 230, 0) 48%),
                radial-gradient(circle at 88% 18%, rgba(14, 111, 186, 0.18) 0%, rgba(14, 111, 186, 0) 40%),
                linear-gradient(145deg, var(--rs-bg-a) 0%, var(--rs-bg-b) 100%);
            box-shadow: var(--rs-shadow);
            display: flex;
            gap: 14px;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .reservation-show-kicker {
            margin: 0;
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #2f6d9f;
            font-weight: 700;
        }

        .reservation-show-title {
            margin: 6px 0 6px;
            font-size: clamp(24px, 4vw, 34px);
            line-height: 1.05;
            color: var(--rs-text);
            font-weight: 800;
        }

        .reservation-show-sub {
            margin: 0;
            color: var(--rs-muted);
            font-size: 14px;
        }

        .reservation-show-chips {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .reservation-chip {
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid transparent;
            background: #ecf3fb;
            color: #285277;
        }

        .reservation-chip.ok { background: #e7f7ee; border-color: #bce8ce; color: #1d6a3d; }
        .reservation-chip.warn { background: #fff6e6; border-color: #f3deb1; color: #915c07; }
        .reservation-chip.danger { background: #ffeceb; border-color: #f7c4c1; color: #a9362f; }
        .reservation-chip.info { background: #eaf4ff; border-color: #c6def9; color: #1e5f9d; }

        .reservation-show-grid {
            display: grid;
            grid-template-columns: 1.3fr .9fr;
            gap: 14px;
        }

        .reservation-card {
            background: var(--rs-card);
            border: 1px solid var(--rs-line);
            border-radius: 16px;
            box-shadow: var(--rs-shadow);
            overflow: hidden;
        }

        .reservation-card-head {
            padding: 14px 16px;
            border-bottom: 1px solid var(--rs-line);
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .reservation-card-title {
            margin: 0;
            font-size: 15px;
            color: var(--rs-text);
            font-weight: 700;
        }

        .reservation-detail-list {
            margin: 0;
            padding: 10px 14px 14px;
            display: grid;
            gap: 8px;
        }

        .reservation-detail-row {
            border: 1px solid #e5edf7;
            border-radius: 12px;
            padding: 10px 12px;
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 10px;
            align-items: center;
            background: #fcfdff;
        }

        .reservation-detail-key {
            color: #48627d;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .reservation-detail-value {
            color: #163651;
            font-size: 14px;
            font-weight: 600;
            word-break: break-word;
        }

        .reservation-objects-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .reservation-object-head {
            padding: 14px 16px;
            border-bottom: 1px solid var(--rs-line);
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .reservation-object-title {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #3d5b78;
        }

        .reservation-object-body {
            padding: 12px;
            display: grid;
            gap: 8px;
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

        .reservation-actions-row {
            display: flex;
            justify-content: flex-end;
        }

        .payment-form {
            border: 1px dashed #c9dbef;
            border-radius: 12px;
            padding: 10px;
            background: #f8fbff;
            display: grid;
            gap: 8px;
        }

        .payment-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .payment-form label {
            font-size: 12px;
            color: #42617e;
            font-weight: 700;
            margin-bottom: 4px;
            display: inline-block;
        }

        .payment-form input,
        .payment-form select,
        .payment-form textarea {
            width: 100%;
            border: 1px solid #d3deea;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 13px;
            background: #fff;
            color: #183a5b;
        }

        .payment-form-help {
            margin: 0;
            font-size: 12px;
            color: #516b85;
            background: #eef5fd;
            border: 1px solid #d5e4f3;
            border-radius: 9px;
            padding: 8px;
        }

        .reservation-payment-list {
            margin: 0;
            padding: 12px;
            list-style: none;
            display: grid;
            gap: 10px;
        }

        .reservation-payment-item {
            border: 1px solid #e1ebf7;
            border-radius: 12px;
            padding: 10px 12px;
            background: #fbfdff;
            display: grid;
            gap: 6px;
        }

        .reservation-payment-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .reservation-payment-id {
            font-weight: 700;
            color: #1c476d;
            font-size: 13px;
        }

        .reservation-payment-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #325774;
        }

        .reservation-empty {
            margin: 0;
            padding: 14px;
            color: #59738d;
            font-size: 13px;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(9, 15, 28, 0.55);
            z-index: 90;
            padding: 16px;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-card {
            width: min(620px, 100%);
            max-height: 90vh;
            overflow: auto;
            border-radius: 16px;
            border: 1px solid #d5e3f2;
            background: #fff;
            box-shadow: 0 20px 45px rgba(16, 35, 58, 0.20);
            padding: 14px;
            display: grid;
            gap: 10px;
        }

        .modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .modal-title {
            margin: 0;
            color: #173957;
            font-size: 18px;
        }

        .salle-available-list {
            display: grid;
            gap: 8px;
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

        .salle-option-main {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .salle-color-dot {
            width: 11px;
            height: 11px;
            border-radius: 999px;
            border: 1px solid rgba(0, 0, 0, 0.2);
            display: inline-block;
        }

        .salle-option small {
            color: #607a95;
            font-size: 12px;
        }

        @media (max-width: 960px) {
            .reservation-show-grid { grid-template-columns: 1fr; }
            .reservation-objects-grid { grid-template-columns: 1fr; }
            .payment-form-row { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .reservation-detail-row { grid-template-columns: 1fr; gap: 4px; }
        }
    </style>

    <section class="reservation-show">
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

        <div class="reservation-show-hero">
            <div>
                <p class="reservation-show-kicker">Reservation detail</p>
                <h1 class="reservation-show-title">{{ $reservation->title ?: 'Reservation #' . $reservation->id }}</h1>
                <p class="reservation-show-sub">Client: {{ $clientFullName }} | Salle: {{ $reservation->salle?->name ?? '-' }}</p>
                <div class="reservation-show-chips">
                    <span class="reservation-chip {{ $statusTone }}">{{ $statusLabel }}</span>
                    <span class="reservation-chip">Du {{ $reservation->start_date }} au {{ $reservation->end_date }}</span>
                    <span class="reservation-chip">{{ $reservation->start_time ?? '--:--' }} - {{ $reservation->end_time ?? '--:--' }}</span>
                </div>
            </div>
            <a href="{{ route('reservations.index') }}" class="btn">Retour au calendrier</a>
        </div>

        <div class="reservation-show-grid">
            <article class="reservation-card">
                <div class="reservation-card-head">
                    <h2 class="reservation-card-title">Informations reservation</h2>
                </div>
                <div class="reservation-detail-list">
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Titre event</span><span class="reservation-detail-value">{{ $reservation->title ?? '-' }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Date debut</span><span class="reservation-detail-value">{{ $reservation->start_date }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Date fin</span><span class="reservation-detail-value">{{ $reservation->end_date }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Heure debut</span><span class="reservation-detail-value">{{ $reservation->start_time ?? '-' }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Heure fin</span><span class="reservation-detail-value">{{ $reservation->end_time ?? '-' }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Statut</span><span class="reservation-detail-value">{{ $statusLabel }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Note admin</span><span class="reservation-detail-value">{{ $reservation->note_admin ?? '-' }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Creee le</span><span class="reservation-detail-value">{{ $reservation->created_at?->format('d/m/Y H:i') }}</span></div>
                </div>
            </article>

            <article class="reservation-card">
                <div class="reservation-card-head">
                    <h2 class="reservation-card-title">Synthese paiement</h2>
                </div>
                <div class="reservation-detail-list">
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Total reservation</span><span class="reservation-detail-value">{{ number_format($totalAmount, 2, '.', ' ') }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Total paye</span><span class="reservation-detail-value">{{ number_format($totalPaid, 2, '.', ' ') }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Reste</span><span class="reservation-detail-value">{{ number_format($remainingAmount, 2, '.', ' ') }}</span></div>
                </div>
            </article>
        </div>

        <div class="reservation-objects-grid">
            <article class="reservation-card">
                <div class="reservation-object-head">
                    <h3 class="reservation-object-title">Client</h3>
                    @if ($canUpdateReservation)
                        <button type="button" class="btn" data-open-modal="client-modal">Modifier</button>
                    @endif
                </div>
                <div class="reservation-object-body">
                    <div class="reservation-kv"><span class="reservation-kv-key">Nom complet</span><span class="reservation-kv-value">{{ $clientFullName }}</span></div>
                    <div class="reservation-kv"><span class="reservation-kv-key">CIN</span><span class="reservation-kv-value">{{ $reservation->client?->cin ?? '-' }}</span></div>
                    <div class="reservation-kv"><span class="reservation-kv-key">Telephone</span><span class="reservation-kv-value">{{ $reservation->client?->phone ?? '-' }}</span></div>
                </div>
            </article>

            <article class="reservation-card">
                <div class="reservation-object-head">
                    <h3 class="reservation-object-title">Salle</h3>
                    @if ($canUpdateReservation)
                        <button type="button" class="btn" data-open-modal="salle-modal">Verifier et changer</button>
                    @endif
                </div>
                <div class="reservation-object-body">
                    <div class="reservation-kv"><span class="reservation-kv-key">Salle actuelle</span><span class="reservation-kv-value">{{ $reservation->salle?->name ?? '-' }}</span></div>
                    <div class="reservation-kv"><span class="reservation-kv-key">Capacite</span><span class="reservation-kv-value">{{ $reservation->salle?->capacity ?? '-' }}</span></div>
                    <div class="reservation-kv"><span class="reservation-kv-key">Type</span><span class="reservation-kv-value">{{ $reservation->salle?->salle_type ?? '-' }}</span></div>
                    <div class="reservation-kv"><span class="reservation-kv-key">Creneau</span><span class="reservation-kv-value">{{ $reservation->start_date }} {{ $reservation->start_time ?? '--:--' }} -> {{ $reservation->end_date }} {{ $reservation->end_time ?? '--:--' }}</span></div>
                </div>
            </article>

            <article class="reservation-card">
                <div class="reservation-object-head">
                    <h3 class="reservation-object-title">Paiements</h3>
                    <span class="reservation-chip info">{{ $paymentCount }} element(s)</span>
                </div>

                <div class="reservation-object-body">
                    @if ($canCreatePayment)
                        <form method="POST" action="{{ route('reservations.payments.store', $reservation) }}" class="payment-form" id="reservation-payment-form">
                            @csrf
                            <div class="payment-form-row">
                                <div>
                                    <label for="payment-phase">Type paiement</label>
                                    <select id="payment-phase" name="phase" required>
                                        <option value="avance" {{ old('phase', $nextPhase) === 'avance' ? 'selected' : '' }}>Avance</option>
                                        <option value="partie-1" {{ old('phase', $nextPhase) === 'partie-1' ? 'selected' : '' }}>Partie 1</option>
                                        <option value="partie-2" {{ old('phase', $nextPhase) === 'partie-2' ? 'selected' : '' }}>Partie 2</option>
                                        <option value="partie-3" {{ old('phase', $nextPhase) === 'partie-3' ? 'selected' : '' }}>Partie 3</option>
                                        <option value="reste" {{ old('phase', $nextPhase) === 'reste' ? 'selected' : '' }}>Reste</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="payment-amount">Montant</label>
                                    <input id="payment-amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required>
                                </div>
                            </div>

                            <div class="payment-form-row">
                                <div>
                                    <label for="payment-method">Methode</label>
                                    <select id="payment-method" name="method" required>
                                        <option value="cash" {{ old('method') === 'cash' ? 'selected' : '' }}>Cash</option>
                                        <option value="virement" {{ old('method') === 'virement' ? 'selected' : '' }}>Virement</option>
                                        <option value="carte" {{ old('method') === 'carte' ? 'selected' : '' }}>Carte</option>
                                        <option value="cheque" {{ old('method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="payment-paid-at">Date paiement</label>
                                    <input id="payment-paid-at" type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}">
                                </div>
                            </div>

                            <div>
                                <label for="payment-note">Note</label>
                                <textarea id="payment-note" name="note" rows="2" placeholder="Note optionnelle">{{ old('note') }}</textarea>
                            </div>

                            <p class="payment-form-help" id="payment-form-help">Controle: le premier paiement doit etre "Avance". Reste actuel: {{ number_format($remainingAmount, 2, '.', ' ') }}.</p>

                            <div class="reservation-actions-row">
                                <button type="submit" class="btn btn-primary" {{ $remainingAmount <= 0 ? 'disabled' : '' }}>Ajouter paiement</button>
                            </div>
                        </form>
                    @endif

                    @if ($reservation->payments->isEmpty())
                        <p class="reservation-empty">Aucun paiement lie a cette reservation.</p>
                    @else
                        <ul class="reservation-payment-list">
                            @foreach ($reservation->payments as $payment)
                                @php
                                    $paymentStatus = (string) ($payment->status ?? 'pending');
                                    $paymentTone = match ($paymentStatus) {
                                        'paid', 'confirmed', 'completed' => 'ok',
                                        'cancelled', 'failed' => 'danger',
                                        default => 'warn',
                                    };
                                @endphp
                                <li class="reservation-payment-item">
                                    <div class="reservation-payment-top">
                                        <span class="reservation-payment-id">{{ $payment->reference ?: ('Paiement #' . $payment->id) }}</span>
                                        <span class="reservation-chip {{ $paymentTone }}">{{ $payment->status ?? 'pending' }}</span>
                                    </div>
                                    <div class="reservation-payment-meta">
                                        <span>Montant</span>
                                        <strong>{{ number_format((float) ($payment->amount ?? 0), 2, '.', ' ') }}</strong>
                                    </div>
                                    <div class="reservation-payment-meta">
                                        <span>Date</span>
                                        <strong>{{ $payment->paid_at ? \Illuminate\Support\Carbon::parse($payment->paid_at)->format('d/m/Y') : '-' }}</strong>
                                    </div>
                                    <div class="reservation-payment-meta">
                                        <span>Methode</span>
                                        <strong>{{ $payment->method ?? '-' }}</strong>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </article>
        </div>
    </section>

    @if ($canUpdateReservation)
        <div class="modal-overlay" id="client-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Modifier client de la reservation</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>

                <form method="POST" action="{{ route('reservations.client.update', $reservation) }}" style="display:grid; gap:10px;">
                    @csrf
                    @method('PATCH')
                    <label for="reservation-client-id">Client</label>
                    <select id="reservation-client-id" name="client_id" class="search" style="max-width:none;" required>
                        @foreach ($clients as $client)
                            @php
                                $optionName = trim((string) (($client->first_name ?? '') . ' ' . ($client->name ?? '')));
                                $optionName = $optionName !== '' ? $optionName : ($client->name ?? 'Client');
                            @endphp
                            <option value="{{ $client->id }}" {{ (int) $reservation->client_id === (int) $client->id ? 'selected' : '' }}>{{ $optionName }}{{ $client->cin ? ' - CIN: ' . $client->cin : '' }}</option>
                        @endforeach
                    </select>
                    <div class="reservation-actions-row">
                        <button type="submit" class="btn btn-primary">Enregistrer client</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay" id="salle-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Changer salle apres verification</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>

                <p class="payment-form-help" id="salle-status">Clique sur verifier disponibilite pour charger les salles libres.</p>

                <form method="POST" action="{{ route('reservations.salle.update', $reservation) }}" style="display:grid; gap:10px;">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="salle_id" id="salle-selected-id" value="{{ $reservation->salle_id }}" required>

                    <div class="reservation-actions-row" style="justify-content:flex-start;">
                        <button type="button" class="btn" id="verify-salle-availability">Verifier disponibilite</button>
                    </div>

                    <div id="salle-available-list" class="salle-available-list"></div>

                    <div class="reservation-actions-row">
                        <button type="submit" class="btn btn-primary">Enregistrer salle</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const overlays = document.querySelectorAll('.modal-overlay');
            const openers = document.querySelectorAll('[data-open-modal]');
            const closers = document.querySelectorAll('[data-close-modal]');

            const openModal = (id) => {
                const target = document.getElementById(id);
                if (target) {
                    target.classList.add('show');
                }
            };

            const closeModal = (element) => {
                if (element) {
                    element.classList.remove('show');
                }
            };

            openers.forEach((button) => {
                button.addEventListener('click', () => {
                    const modalId = button.getAttribute('data-open-modal');
                    if (modalId) {
                        openModal(modalId);
                    }
                });
            });

            closers.forEach((button) => {
                button.addEventListener('click', () => {
                    closeModal(button.closest('.modal-overlay'));
                });
            });

            overlays.forEach((overlay) => {
                overlay.addEventListener('click', (event) => {
                    if (event.target === overlay) {
                        closeModal(overlay);
                    }
                });
            });

            const paymentPhase = document.getElementById('payment-phase');
            const paymentAmount = document.getElementById('payment-amount');
            const paymentHelp = document.getElementById('payment-form-help');
            const remainingValue = Number("{{ number_format($remainingAmount, 2, '.', '') }}");
            const paymentCountValue = Number("{{ $paymentCount }}");

            const syncPaymentControls = () => {
                if (!paymentPhase || !paymentAmount) {
                    return;
                }

                if (paymentCountValue === 0 && paymentPhase.value !== 'avance') {
                    paymentPhase.value = 'avance';
                }

                paymentAmount.max = remainingValue > 0 ? String(remainingValue) : '0';

                if (paymentPhase.value === 'reste' && remainingValue > 0) {
                    paymentAmount.value = remainingValue.toFixed(2);
                    paymentAmount.readOnly = true;
                } else {
                    paymentAmount.readOnly = false;
                }

                if (paymentHelp) {
                    paymentHelp.textContent = `Controle: premier paiement = Avance. Reste actuel: ${remainingValue.toFixed(2)}.`;
                }
            };

            if (paymentPhase) {
                paymentPhase.addEventListener('change', syncPaymentControls);
                syncPaymentControls();
            }

            const verifyButton = document.getElementById('verify-salle-availability');
            const salleList = document.getElementById('salle-available-list');
            const salleStatus = document.getElementById('salle-status');
            const salleSelectedId = document.getElementById('salle-selected-id');
            const availabilityRoute = "{{ route('reservations.available-salles', $reservation) }}";

            const renderSalles = (salles) => {
                if (!salleList) {
                    return;
                }

                salleList.innerHTML = '';

                if (!Array.isArray(salles) || salles.length === 0) {
                    salleList.innerHTML = '<p class="reservation-empty">Aucune salle disponible sur ce creneau.</p>';
                    return;
                }

                salles.forEach((salle) => {
                    const row = document.createElement('label');
                    row.className = 'salle-option';
                    row.innerHTML = `
                        <span class="salle-option-main">
                            <input type="radio" name="salle-choice" value="${String(salle.id)}" ${String(salle.id) === String(salleSelectedId?.value || '') ? 'checked' : ''}>
                            <span class="salle-color-dot" style="background:${salle.color_code || '#3b82f6'};"></span>
                            <strong>${salle.name}</strong>
                        </span>
                        <small>Cap: ${salle.capacity ?? '-'} | Prix: ${salle.price_per_day ?? '-'}</small>
                    `;

                    const radio = row.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.addEventListener('change', () => {
                            if (salleSelectedId) {
                                salleSelectedId.value = radio.value;
                            }
                        });
                    }

                    salleList.appendChild(row);
                });
            };

            if (verifyButton) {
                verifyButton.addEventListener('click', async () => {
                    if (salleStatus) {
                        salleStatus.textContent = 'Verification des salles disponibles...';
                    }

                    try {
                        const response = await fetch(availabilityRoute, {
                            headers: {
                                Accept: 'application/json',
                            },
                        });

                        const payload = await response.json();
                        if (!response.ok) {
                            throw new Error(payload?.message || 'Erreur lors de la verification.');
                        }

                        renderSalles(payload.salles || []);

                        if (salleStatus) {
                            const count = Array.isArray(payload.salles) ? payload.salles.length : 0;
                            salleStatus.textContent = count > 0
                                ? `${count} salle(s) disponible(s). Choisis une salle puis enregistre.`
                                : 'Aucune salle disponible sur ce creneau.';
                        }
                    } catch (error) {
                        if (salleStatus) {
                            salleStatus.textContent = error instanceof Error ? error.message : 'Erreur de verification de disponibilite.';
                        }
                    }
                });
            }
        })();
    </script>
@endsection
