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
    @endphp

    <style>
        .reservation-show {
            --rs-bg-a: #f6fbff;
            --rs-bg-b: #eef6ff;
            --rs-card: #ffffff;
            --rs-line: #dbe7f4;
            --rs-text: #14314d;
            --rs-muted: #55708c;
            --rs-accent: #0f6eb9;
            --rs-shadow: 0 14px 36px rgba(20, 49, 77, 0.10);
            display: grid;
            gap: 14px;
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

        @media (max-width: 960px) {
            .reservation-show-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .reservation-detail-row { grid-template-columns: 1fr; gap: 4px; }
        }
    </style>

    <section class="reservation-show">
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
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Client</span><span class="reservation-detail-value">{{ $clientFullName }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Salle</span><span class="reservation-detail-value">{{ $reservation->salle?->name ?? '-' }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Date debut</span><span class="reservation-detail-value">{{ $reservation->start_date }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Date fin</span><span class="reservation-detail-value">{{ $reservation->end_date }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Heure debut</span><span class="reservation-detail-value">{{ $reservation->start_time ?? '-' }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Heure fin</span><span class="reservation-detail-value">{{ $reservation->end_time ?? '-' }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Statut</span><span class="reservation-detail-value">{{ $statusLabel }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Montant total</span><span class="reservation-detail-value">{{ number_format((float) ($reservation->total_amount ?? 0), 2, '.', ' ') }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Note admin</span><span class="reservation-detail-value">{{ $reservation->note_admin ?? '-' }}</span></div>
                    <div class="reservation-detail-row"><span class="reservation-detail-key">Creee le</span><span class="reservation-detail-value">{{ $reservation->created_at?->format('d/m/Y H:i') }}</span></div>
                </div>
            </article>

            <article class="reservation-card">
                <div class="reservation-card-head">
                    <h2 class="reservation-card-title">Paiements lies</h2>
                    <span class="reservation-chip info">{{ $reservation->payments->count() }} element(s)</span>
                </div>

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
                                    <span class="reservation-payment-id">Paiement #{{ $payment->id }}</span>
                                    <span class="reservation-chip {{ $paymentTone }}">{{ $payment->status ?? 'pending' }}</span>
                                </div>
                                <div class="reservation-payment-meta">
                                    <span>Montant</span>
                                    <strong>{{ number_format((float) ($payment->amount ?? 0), 2, '.', ' ') }}</strong>
                                </div>
                                <div class="reservation-payment-meta">
                                    <span>Date</span>
                                    <strong>{{ $payment->payment_date ?? '-' }}</strong>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        </div>
    </section>
@endsection
