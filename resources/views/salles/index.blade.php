@extends('layouts.app')

@section('content')
    <section class="panel">
        <h1 class="panel-title">Salles</h1>
        <p class="panel-sub">Inventaire des salles et capacites.</p>

        <div class="page-actions">
            <a class="btn" href="{{ route('dashboard') }}">Retour dashboard</a>
        </div>

        <div style="overflow-x:auto; margin-top: 12px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Capacite</th>
                        <th>Prix/Jour</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($salles as $salle)
                        <tr>
                            <td>{{ $salle->id }}</td>
                            <td>{{ $salle->name }}</td>
                            <td>{{ $salle->capacity }}</td>
                            <td>{{ number_format((float) $salle->price_per_day, 2, '.', ' ') }}</td>
                            <td>{{ $salle->status ?? 'active' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Aucune salle.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
