@extends('layouts.app')

@section('content')
    <section class="panel">
        <h1 class="panel-title">Projects Classic Dashboard</h1>
        <p class="panel-sub">Vue globale des operations QueenPark avec un style admin moderne.</p>

        <div class="page-actions">
            <a class="btn btn-primary" href="{{ route('reservations.index') }}">Voir les reservations</a>
            <a class="btn" href="{{ route('clients.index') }}">Gerer les clients</a>
            <a class="btn" href="{{ route('payments.index') }}">Suivre les paiements</a>
        </div>

        <div class="kpi-grid">
            <article class="kpi-card">
                <div class="kpi-label">Utilisateurs</div>
                <div class="kpi-value">{{ $stats['users'] ?? 0 }}</div>
                <div class="kpi-foot">Comptes enregistres</div>
            </article>
            <article class="kpi-card">
                <div class="kpi-label">Clients</div>
                <div class="kpi-value">{{ $stats['clients'] ?? 0 }}</div>
                <div class="kpi-foot">Base clients active</div>
            </article>
            <article class="kpi-card">
                <div class="kpi-label">Salles</div>
                <div class="kpi-value">{{ $stats['salles'] ?? 0 }}</div>
                <div class="kpi-foot">Ressources disponibles</div>
            </article>
            <article class="kpi-card">
                <div class="kpi-label">Reservations</div>
                <div class="kpi-value">{{ $stats['reservations'] ?? 0 }}</div>
                <div class="kpi-foot">Demandes totalisees</div>
            </article>
        </div>

        <div class="kpi-grid" style="grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 12px;">
            <article class="kpi-card">
                <div class="kpi-label">Paiements</div>
                <div class="kpi-value">{{ $stats['payments'] ?? 0 }}</div>
                <div class="kpi-foot">Transactions enregistrees</div>
            </article>
            <article class="kpi-card">
                <div class="kpi-label">Montant encaisse</div>
                <div class="kpi-value">{{ number_format((float) ($stats['payments_total'] ?? 0), 2, '.', ' ') }}</div>
                <div class="kpi-foot">Somme des paiements</div>
            </article>
        </div>
    </section>

    <section class="content-grid">
        <div class="panel">
            <h2 class="panel-title">Reservations recentes</h2>
            <p class="panel-sub">Derniers dossiers crees dans le systeme.</p>
            <div style="overflow-x:auto; margin-top: 12px;">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Salle</th>
                            <th>Debut</th>
                            <th>Fin</th>
                            <th>Statut</th>
                            <th>Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentReservations as $reservation)
                            <tr>
                                <td>{{ $reservation->id }}</td>
                                <td>{{ $reservation->client?->name ?? 'N/A' }}</td>
                                <td>{{ $reservation->salle?->name ?? 'N/A' }}</td>
                                <td>@frDate($reservation->start_date)</td>
                                <td>@frDate($reservation->end_date)</td>
                                <td>
                                    @php
                                        $status = strtolower((string) $reservation->status);
                                        $badge = match ($status) {
                                            'confirmed', 'paid', 'active' => 'badge-success',
                                            'cancelled', 'failed' => 'badge-danger',
                                            'pending' => 'badge-warning',
                                            default => 'badge-info',
                                        };
                                    @endphp
                                    <span class="badge {{ $badge }}">{{ $reservation->status ?? 'pending' }}</span>
                                </td>
                                <td>{{ number_format((float) $reservation->total_amount, 2, '.', ' ') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="muted">Aucune reservation recente.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
