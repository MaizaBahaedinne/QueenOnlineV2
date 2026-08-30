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

        @media (max-width: 860px) {
            .payment-form-row {
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
                    </div>
                    <div class="payment-form-row">
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
                        <div>
                            <label for="entree-heure-prevu">Heure prevue</label>
                            <input id="entree-heure-prevu" name="heure_prevu" type="time" value="{{ old('heure_prevu') }}">
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
                <div style="display:grid;gap:8px;">
                    @foreach ($serviceEntreesRows as $entree)
                        @php
                            $totalRetourne = (int) $entree->retours->sum('quantite_retournee');
                            $reste = max(((int) $entree->quantite) - $totalRetourne, 0);
                        @endphp
                        <div class="additional-service-category">
                            <h4>{{ $entree->nature }} (Qte entree: {{ (int) $entree->quantite }})</h4>
                            <small style="color:#5f7690;">
                                Moment: {{ $entree->moment_service ?: '-' }} | Heure: {{ $entree->heure_prevu ? \Illuminate\Support\Carbon::parse($entree->heure_prevu)->format('H:i') : '--:--' }}
                                | Cree le:
                                @if ($entree->created_dtm)
                                    @frDateTime($entree->created_dtm)
                                @else
                                    @frDateTime($entree->created_at)
                                @endif
                                | Cree par: {{ $entree->creator?->name ?? '-' }}
                            </small>
                            <div class="reservation-kv">
                                <span class="reservation-kv-key">Inventaire</span>
                                <span class="reservation-kv-value">Sorti: {{ $totalRetourne }} | Reste a consommer: {{ $reste }}</span>
                            </div>

                            @if ($entree->retours->isNotEmpty())
                                <div style="display:grid;gap:6px;">
                                    @foreach ($entree->retours->sortByDesc(function ($retour) { return $retour->created_dtm ?? $retour->created_at; }) as $retour)
                                        <div class="salle-option" style="background:#fff;">
                                            <div>
                                                <strong>Sortie: {{ (int) $retour->quantite_retournee }}</strong>
                                                <small>
                                                    @if ($retour->created_dtm)
                                                        @frDateTime($retour->created_dtm)
                                                    @else
                                                        @frDateTime($retour->created_at)
                                                    @endif
                                                    | Cree par: {{ $retour->creator?->name ?? '-' }}
                                                    @if (!empty($retour->note_retour)) - {{ $retour->note_retour }} @endif
                                                </small>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if ($reste > 0 && $canUpdateReservation && ($reservation->status ?? null) !== 'cancelled')
                                <form method="POST" action="{{ route('reservations.service-retours.store', [$reservation, $entree]) }}" class="payment-form" style="margin-top:6px;">
                                    @csrf
                                    <div class="payment-form-row">
                                        <div>
                                            <label>Quantite sortie</label>
                                            <input type="number" name="quantite_retournee" min="1" max="{{ $reste }}" required>
                                        </div>
                                        <div>
                                            <label>Note sortie</label>
                                            <input type="text" name="note_retour" placeholder="Optionnel">
                                        </div>
                                    </div>
                                    <div class="reservation-actions-row">
                                        <button type="submit" class="btn">Enregistrer sortie</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </article>
    </section>
@endsection
