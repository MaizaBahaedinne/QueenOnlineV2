@extends('layouts.app')

@section('content')
    @php
        $clientName = trim((string) (($reservation->client?->first_name ?? '') . ' ' . ($reservation->client?->name ?? '')));
        $clientName = $clientName !== '' ? $clientName : ($reservation->client?->name ?? '-');
        $totalPaid = (float) $reservation->payments->sum('amount');
        $remaining = max((float) $totalTtc - $totalPaid, 0);
    @endphp

    <section class="panel" style="display:grid;gap:14px;">
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
            <div>
                <h1 class="panel-title">Facture reservation</h1>
                <p class="panel-sub">Reservation #{{ $reservation->id }} - TVA 19% incluse</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('reservations.show', $reservation) }}" class="btn">Retour reservation</a>
                <button type="button" class="btn btn-primary" onclick="window.print()">Imprimer</button>
            </div>
        </div>

        <article class="reservation-card">
            <div class="reservation-object-head">
                <h3 class="reservation-object-title">Detail facture</h3>
            </div>
            <div class="reservation-object-body">
                <div class="reservation-kv"><span class="reservation-kv-key">Client</span><span class="reservation-kv-value">{{ $clientName }}</span></div>
                <div class="reservation-kv"><span class="reservation-kv-key">Salle</span><span class="reservation-kv-value">{{ $reservation->salle?->name ?? '-' }}</span></div>
                <div class="reservation-kv"><span class="reservation-kv-key">Montant HT</span><span class="reservation-kv-value">{{ number_format((float) $totalHt, 3, '.', ' ') }} TND</span></div>
                <div class="reservation-kv"><span class="reservation-kv-key">TVA (19%)</span><span class="reservation-kv-value">{{ number_format((float) $tvaAmount, 3, '.', ' ') }} TND</span></div>
                <div class="reservation-kv"><span class="reservation-kv-key">Montant TTC</span><span class="reservation-kv-value">{{ number_format((float) $totalTtc, 3, '.', ' ') }} TND</span></div>
                <div class="reservation-kv"><span class="reservation-kv-key">Total paye</span><span class="reservation-kv-value">{{ number_format((float) $totalPaid, 3, '.', ' ') }} TND</span></div>
                <div class="reservation-kv"><span class="reservation-kv-key">Reste</span><span class="reservation-kv-value">{{ number_format((float) $remaining, 3, '.', ' ') }} TND</span></div>
            </div>
        </article>
    </section>
@endsection
