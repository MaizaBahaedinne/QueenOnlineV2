@extends('layouts.app')

@section('content')
    <section class="panel">
        <h1 class="panel-title">Reservations</h1>
        <p class="panel-sub">Planning et suivi des reservations.</p>

        <div class="page-actions">
            <a class="btn" href="{{ route('dashboard') }}">Retour dashboard</a>
        </div>

        <div style="overflow-x:auto; margin-top: 12px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Salle</th>
                        <th>Debut</th>
                        <th>Fin</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reservations as $reservation)
                        <tr>
                            <td>{{ $reservation->id }}</td>
                            <td>{{ $reservation->client?->name ?? '-' }}</td>
                            <td>{{ $reservation->salle?->name ?? '-' }}</td>
                            <td>{{ $reservation->start_date }}</td>
                            <td>{{ $reservation->end_date }}</td>
                            <td>{{ $reservation->status ?? 'pending' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Aucune reservation.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
