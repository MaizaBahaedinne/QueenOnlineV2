@extends('layouts.app')

@section('content')
    <section class="panel">
        <h1 class="panel-title">Utilisateurs</h1>
        <p class="panel-sub">Gestion des comptes internes.</p>

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
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? '-' }}</td>
                            <td>{{ $user->status ?? 'active' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">Aucun utilisateur.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
