@extends('layouts.app')

@section('content')
    @php
        $clientName = trim((string) (($reservation->client?->first_name ?? '') . ' ' . ($reservation->client?->name ?? '')));
        $clientName = $clientName !== '' ? $clientName : ($reservation->client?->name ?? '-');
    @endphp

    <section class="panel" style="display:grid;gap:14px;">
        <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center;">
            <div>
                <h1 class="panel-title">Contrat reservation</h1>
                <p class="panel-sub">Reservation #{{ $reservation->id }} - {{ $reservation->title ?: 'Sans titre' }}</p>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('reservations.show', $reservation) }}" class="btn">Retour reservation</a>
                <button type="button" class="btn btn-primary" onclick="window.print()">Imprimer</button>
            </div>
        </div>

        <article class="reservation-card">
            <div class="reservation-object-head">
                <h3 class="reservation-object-title">Informations contrat</h3>
            </div>
            <div class="reservation-object-body">
                <div class="reservation-kv"><span class="reservation-kv-key">Client</span><span class="reservation-kv-value">{{ $clientName }}</span></div>
                <div class="reservation-kv"><span class="reservation-kv-key">Salle</span><span class="reservation-kv-value">{{ $reservation->salle?->name ?? '-' }}</span></div>
                <div class="reservation-kv"><span class="reservation-kv-key">Date evenement</span><span class="reservation-kv-value">@frDate($reservation->start_date) {{ $reservation->start_time ? ' - ' . substr((string) $reservation->start_time, 0, 5) : '' }}</span></div>
                <div class="reservation-kv"><span class="reservation-kv-key">Montant convenu</span><span class="reservation-kv-value">{{ number_format((float) ($reservation->total_amount ?? 0), 3, '.', ' ') }} TND</span></div>
                <div class="reservation-kv"><span class="reservation-kv-key">Conditions</span><span class="reservation-kv-value">Ce document sert de base contractuelle de reservation entre les deux parties.</span></div>
            </div>
        </article>
    </section>
@endsection
