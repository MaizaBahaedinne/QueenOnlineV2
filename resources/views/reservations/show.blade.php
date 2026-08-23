@extends('layouts.app')

@section('content')
    <section class="panel">
        <div style="display:flex; justify-content:space-between; gap:10px; align-items:center; flex-wrap:wrap;">
            <div>
                <h1 class="panel-title">Reservation #{{ $reservation->id }}</h1>
                <p class="panel-sub">Fiche detaillee de la reservation.</p>
            </div>
            <a href="{{ route('reservations.index') }}" class="btn">Retour au calendrier</a>
        </div>

        <div class="content-grid" style="margin-top:14px;">
            <div class="panel" style="box-shadow:none;">
                <h2 class="panel-title" style="font-size:16px; margin-bottom:10px;">Informations reservation</h2>
                <table>
                    <tbody>
                        <tr><th style="width:220px;">Titre event</th><td>{{ $reservation->title ?? '-' }}</td></tr>
                        <tr><th style="width:220px;">Client</th><td>{{ $reservation->client?->first_name }} {{ $reservation->client?->name }}</td></tr>
                        <tr><th>Salle</th><td>{{ $reservation->salle?->name ?? '-' }}</td></tr>
                        <tr><th>Date debut</th><td>{{ $reservation->start_date }}</td></tr>
                        <tr><th>Date fin</th><td>{{ $reservation->end_date }}</td></tr>
                        <tr><th>Heure debut</th><td>{{ $reservation->start_time ?? '-' }}</td></tr>
                        <tr><th>Heure fin</th><td>{{ $reservation->end_time ?? '-' }}</td></tr>
                        <tr><th>Statut</th><td>{{ $reservation->status ?? 'pending' }}</td></tr>
                        <tr><th>Montant total</th><td>{{ number_format((float) ($reservation->total_amount ?? 0), 2, '.', ' ') }}</td></tr>
                        <tr><th>Note admin</th><td>{{ $reservation->note_admin ?? '-' }}</td></tr>
                        <tr><th>Creee le</th><td>{{ $reservation->created_at?->format('d/m/Y H:i') }}</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="panel" style="box-shadow:none;">
                <h2 class="panel-title" style="font-size:16px; margin-bottom:10px;">Paiements lies</h2>
                @if ($reservation->payments->isEmpty())
                    <p class="muted">Aucun paiement lie a cette reservation.</p>
                @else
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Montant</th>
                                <th>Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reservation->payments as $payment)
                                <tr>
                                    <td>{{ $payment->id }}</td>
                                    <td>{{ number_format((float) ($payment->amount ?? 0), 2, '.', ' ') }}</td>
                                    <td>{{ $payment->payment_date ?? '-' }}</td>
                                    <td>{{ $payment->status ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </section>
@endsection
