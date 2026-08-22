@extends('layouts.app')

@section('content')
    <section class="panel">
        <h1 class="panel-title">Gestion des roles utilisateurs</h1>
        <p class="panel-sub">Creation, modification et affectation des roles.</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        @if (session('error'))
            <p class="badge badge-danger" style="margin-top:10px;">{{ session('error') }}</p>
        @endif

        <div class="content-grid" style="margin-top:14px;">
            <div class="panel" style="box-shadow:none;">
                <h2 class="panel-title">Ajouter un role</h2>
                <form method="POST" action="{{ route('roles.store') }}" style="display:grid; gap:10px; margin-top:8px;">
                    @csrf
                    <input class="search" style="max-width:none;" type="text" name="name" placeholder="Nom du role" required>
                    <input class="search" style="max-width:none;" type="text" name="slug" placeholder="Slug (ex: superviseur)" required>
                    <input class="search" style="max-width:none;" type="text" name="description" placeholder="Description">
                    <button class="btn btn-primary" type="submit" style="width:max-content;">Ajouter</button>
                </form>
            </div>

            <div class="panel" style="box-shadow:none;">
                <h2 class="panel-title">Roles existants</h2>
                <div style="overflow-x:auto; margin-top:10px;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Slug</th>
                                <th>Description</th>
                                <th>Utilisateurs</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($roles as $role)
                                <tr>
                                    <td>{{ $role->id }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('roles.update', $role) }}" style="display:grid; gap:8px; min-width:220px;">
                                            @csrf
                                            @method('PATCH')
                                            <input class="search" style="max-width:none;" type="text" name="name" value="{{ $role->name }}" required>
                                    </td>
                                    <td>
                                            <input class="search" style="max-width:none;" type="text" name="slug" value="{{ $role->slug }}" required>
                                    </td>
                                    <td>
                                            <input class="search" style="max-width:none;" type="text" name="description" value="{{ $role->description }}">
                                    </td>
                                    <td>{{ $role->users_count }}</td>
                                    <td>
                                            <button class="btn" type="submit">Enregistrer</button>
                                        </form>

                                        <form method="POST" action="{{ route('roles.destroy', $role) }}" style="margin-top:8px;" onsubmit="return confirm('Supprimer ce role ?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn" type="submit">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="muted">Aucun role defini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel" style="box-shadow:none;">
                <h2 class="panel-title">Affectation role par utilisateur</h2>
                <div style="overflow-x:auto; margin-top:10px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Email</th>
                                <th>Role actuel</th>
                                <th>Changer role</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->role?->name ?? '-' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('roles.users.update', $user) }}" style="display:flex; gap:8px; align-items:center;">
                                            @csrf
                                            @method('PATCH')
                                            <select class="search" style="max-width:none; min-width:180px;" name="role_id">
                                                <option value="">Aucun role</option>
                                                @foreach ($roles as $role)
                                                    <option value="{{ $role->id }}" {{ $user->role_id === $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn" type="submit">Affecter</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="muted">Aucun utilisateur.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
