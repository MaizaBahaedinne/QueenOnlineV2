@extends('layouts.app')

@section('content')
    @php
        $canUpdate = auth()->user()?->canFeature('clients', 'update', 'update') ?? false;
        $balance = (float) ($clientCreditBalance ?? 0);
        $fullName = trim((string) (($client->first_name ?? '') . ' ' . ($client->name ?? '')));
        $fullName = $fullName !== '' ? $fullName : ($client->name ?? ('Client #' . $client->id));
        $reservationCount = $reservations->count();
        $paymentCount = $payments->count();
        $paidTotal = (float) $payments->sum('amount');
        $balancesByService = is_array($clientCreditBalancesByService ?? null) ? $clientCreditBalancesByService : [];
        $creditServiceLabels = is_array($creditServiceLabels ?? null) ? $creditServiceLabels : [];
    @endphp

    <style>
        .client-show {
            display: grid;
            gap: 14px;
        }

        .client-show-hero {
            border: 1px solid #d5e3f2;
            border-radius: 16px;
            padding: 16px;
            background: linear-gradient(140deg, #f6fbff 0%, #eef6ff 100%);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            flex-wrap: wrap;
        }

        .client-show-title {
            margin: 0;
            font-size: 28px;
            color: #14314d;
            font-weight: 800;
        }

        .client-show-sub {
            margin: 6px 0 0;
            color: #4e6a85;
            font-size: 14px;
        }

        .client-chip-row {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .client-chip {
            border-radius: 999px;
            border: 1px solid #cfe0f2;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
            color: #224a70;
            background: #f8fbff;
        }

        .client-balance-card {
            min-width: 220px;
            border: 1px solid #cfe0f2;
            border-radius: 14px;
            background: #ffffff;
            padding: 12px;
            display: grid;
            gap: 8px;
        }

        .client-balance-label {
            margin: 0;
            font-size: 12px;
            color: #55708c;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-weight: 700;
        }

        .client-balance-value {
            margin: 0;
            font-size: 28px;
            color: #153b5e;
            font-weight: 800;
        }

        .client-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .client-card {
            border: 1px solid #dbe7f4;
            border-radius: 14px;
            background: #fff;
            overflow: hidden;
        }

        .client-card-head {
            padding: 12px 14px;
            border-bottom: 1px solid #dbe7f4;
            background: #f8fbff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .client-card-title {
            margin: 0;
            color: #1e4f7b;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .client-list {
            margin: 0;
            padding: 10px 12px 12px;
            list-style: none;
            display: grid;
            gap: 8px;
        }

        .client-row {
            border: 1px solid #e5edf7;
            border-radius: 10px;
            padding: 9px 10px;
            background: #fcfdff;
            display: grid;
            gap: 3px;
        }

        .client-row-top {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 13px;
            color: #173957;
            font-weight: 700;
        }

        .client-row-sub {
            font-size: 12px;
            color: #5b7690;
        }

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
            max-height: 88vh;
            overflow: auto;
            background: #fff;
            border: 1px solid #dbe7f4;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 20px 40px rgba(17, 40, 68, 0.18);
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
            color: #163651;
            font-size: 18px;
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

        @media (max-width: 960px) {
            .client-grid { grid-template-columns: 1fr; }
        }
    </style>

    <section class="client-show">
        @if (session('success'))
            <p class="badge badge-success" style="margin:0;">{{ session('success') }}</p>
        @endif

        @if ($errors->any())
            <div class="badge" style="background:#fff1f0;color:#9c2f2a;border-color:#f1c6c2;text-align:left;">
                <strong>Verifie les informations:</strong>
                <ul style="margin:6px 0 0 18px;padding:0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <article class="client-show-hero">
            <div>
                <h1 class="client-show-title">{{ $fullName }}</h1>
                <p class="client-show-sub">CIN: {{ $client->cin ?? '-' }} | Mobile: {{ $client->phone ?? '-' }} | Email: {{ $client->email ?? '-' }}</p>
                <div class="client-chip-row">
                    <span class="client-chip">Type: {{ ($client->client_type ?? 'personne-physique') === 'societe' ? 'Societe' : 'Personne physique' }}</span>
                    <span class="client-chip">Reservations: {{ $reservationCount }}</span>
                    <span class="client-chip">Paiements: {{ $paymentCount }}</span>
                    <span class="client-chip">Total verse: {{ number_format($paidTotal, 2, '.', ' ') }}</span>
                </div>
            </div>

            <div class="client-balance-card">
                <p class="client-balance-label">Solde de compte</p>
                <p class="client-balance-value">{{ number_format($balance, 2, '.', ' ') }}</p>
                @if (!empty($balancesByService))
                    <div style="display:grid;gap:4px;">
                        @foreach ($creditServiceLabels as $serviceSlug => $serviceLabel)
                            <small style="color:#4e6a85;display:flex;justify-content:space-between;gap:8px;">
                                <span>{{ $serviceLabel }}</span>
                                <strong>{{ number_format((float) ($balancesByService[$serviceSlug] ?? 0), 2, '.', ' ') }}</strong>
                            </small>
                        @endforeach
                    </div>
                @endif
                @if ($canUpdate)
                    <button type="button" class="btn" data-open-modal="client-transfer-credit-modal">Transferer vers un autre client</button>
                @endif
                <a href="{{ route('clients.index') }}" class="btn">Retour clients</a>
            </div>
        </article>

        <div class="client-grid">
            <article class="client-card">
                <div class="client-card-head">
                    <h3 class="client-card-title">Reservations</h3>
                </div>
                @if ($reservations->isEmpty())
                    <p style="padding:12px;color:#5b7690;margin:0;">Aucune reservation.</p>
                @else
                    <ul class="client-list">
                        @foreach ($reservations as $reservation)
                            <li class="client-row">
                                <div class="client-row-top">
                                    <span>{{ $reservation->title ?: ('Reservation #' . $reservation->id) }}</span>
                                    <span>{{ number_format((float) ($reservation->total_amount ?? 0), 2, '.', ' ') }}</span>
                                </div>
                                <div class="client-row-sub">@frDate($reservation->start_date) | {{ $reservation->start_time ?? '--:--' }} - {{ $reservation->end_time ?? '--:--' }} | Salle: {{ $reservation->salle?->name ?? '-' }} | Statut: {{ $reservation->status ?? '-' }}</div>
                                <div>
                                    <a class="btn" href="{{ route('reservations.show', $reservation) }}">Ouvrir reservation</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>

            <article class="client-card">
                <div class="client-card-head">
                    <h3 class="client-card-title">Paiements</h3>
                </div>
                @if ($payments->isEmpty())
                    <p style="padding:12px;color:#5b7690;margin:0;">Aucun paiement.</p>
                @else
                    <ul class="client-list">
                        @foreach ($payments as $payment)
                            <li class="client-row">
                                <div class="client-row-top">
                                    <span>{{ $payment->reference ?: ('Paiement #' . $payment->id) }}</span>
                                    <span>{{ number_format((float) ($payment->amount ?? 0), 2, '.', ' ') }}</span>
                                </div>
                                <div class="client-row-sub">@frDateTime($payment->paid_at) | Methode: {{ $payment->method ?? '-' }} | Recepteur: {{ $payment->user?->name ?? '-' }}</div>
                                <div class="client-row-sub">Reservation: {{ $payment->reservation?->title ?: ('#' . ($payment->reservation_id ?? '-')) }}</div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        </div>
    </section>

    @if ($canUpdate)
        <div class="modal-overlay" id="client-transfer-credit-modal">
            <div class="modal-card" style="width:min(700px,100%);">
                <div class="modal-head">
                    <h3 class="modal-title">Transferer solde client</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>

                <form method="POST" action="{{ route('clients.transfer-credit', $client) }}" style="display:grid;gap:10px;">@csrf
                    <p class="payment-form-help">Source: {{ $fullName }} | Solde disponible: {{ number_format($balance, 2, '.', ' ') }}</p>
                    <div>
                        <label for="transfer-service-slug">Type de service</label>
                        <select id="transfer-service-slug" name="service_slug" required>
                            @foreach ($creditServiceLabels as $serviceSlug => $serviceLabel)
                                <option value="{{ $serviceSlug }}" {{ old('service_slug', 'salles') === $serviceSlug ? 'selected' : '' }}>
                                    {{ $serviceLabel }} ({{ number_format((float) ($balancesByService[$serviceSlug] ?? 0), 2, '.', ' ') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="target-client-id">Client destinataire</label>
                        <select id="target-client-id" name="target_client_id" required>
                            <option value="">Selectionner...</option>
                            @foreach ($otherClients as $otherClient)
                                @php
                                    $otherName = trim((string) (($otherClient->first_name ?? '') . ' ' . ($otherClient->name ?? '')));
                                    $otherLabel = $otherName !== '' ? $otherName : ($otherClient->name ?? ('Client #' . $otherClient->id));
                                @endphp
                                <option value="{{ $otherClient->id }}">{{ $otherLabel }}{{ !empty($otherClient->cin) ? ' - CIN: ' . $otherClient->cin : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="transfer-amount">Montant</label>
                        <input id="transfer-amount" name="amount" type="number" step="0.01" min="0.01" max="{{ number_format($balance, 2, '.', '') }}" required>
                    </div>
                    <div>
                        <label for="transfer-note">Motif</label>
                        <textarea id="transfer-note" name="note" rows="2"></textarea>
                    </div>
                    <div style="display:flex;justify-content:flex-end;">
                        <button type="submit" class="btn btn-primary" {{ $balance <= 0 ? 'disabled' : '' }}>Confirmer transfert</button>
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

            openers.forEach((button) => {
                button.addEventListener('click', () => {
                    const modalId = button.getAttribute('data-open-modal');
                    const target = modalId ? document.getElementById(modalId) : null;
                    if (target) {
                        target.classList.add('show');
                    }
                });
            });

            closers.forEach((button) => {
                button.addEventListener('click', () => {
                    const overlay = button.closest('.modal-overlay');
                    if (overlay) {
                        overlay.classList.remove('show');
                    }
                });
            });

            overlays.forEach((overlay) => {
                overlay.addEventListener('click', (event) => {
                    if (event.target === overlay) {
                        overlay.classList.remove('show');
                    }
                });
            });
        })();
    </script>
@endsection
