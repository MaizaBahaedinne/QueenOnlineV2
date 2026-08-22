@extends('layouts.app')

@section('content')
    <section class="panel">
        <h1 class="panel-title">Clients</h1>
        <p class="panel-sub">Base clients et coordonnees principales.</p>

        <div class="page-actions">
            <a class="btn" href="{{ route('dashboard') }}">Retour dashboard</a>
        </div>

        <div style="overflow-x:auto; margin-top: 12px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Telephone</th>
                        <th>Ville</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        <tr>
                            <td>{{ $client->id }}</td>
                            <td>{{ $client->name }}</td>
                            <td>{{ $client->email ?? '-' }}</td>
                            <td>{{ $client->phone ?? '-' }}</td>
                            <td>{{ $client->city ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Aucun client.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
