@extends('layouts.app')

@section('content')
    <section class="panel">
        <h1 class="panel-title">Modules et fonctionnalites</h1>
        <p class="panel-sub">Base de donnees modulaire de la plateforme.</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top: 10px;">{{ session('success') }}</p>
        @endif

        <div class="content-grid" style="margin-top: 14px;">
            <div class="panel" style="box-shadow:none;">
                <h2 class="panel-title">Nouveau module</h2>
                <form method="POST" action="{{ route('modules.store') }}" style="display:grid; gap:10px; margin-top: 8px;">
                    @csrf
                    <input name="name" type="text" placeholder="Nom du module" required class="search" style="max-width:none;" />
                    <input name="description" type="text" placeholder="Description" class="search" style="max-width:none;" />
                    <input name="sort_order" type="number" min="0" placeholder="Ordre" class="search" style="max-width:none;" />
                    <button type="submit" class="btn btn-primary" style="width:max-content;">Ajouter module</button>
                </form>
            </div>

            @foreach ($modules as $module)
                <div class="panel" style="box-shadow:none;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                        <div>
                            <h3 class="panel-title" style="margin:0;">{{ $module->name }}</h3>
                            <p class="panel-sub">{{ $module->description ?: 'Aucune description' }} • slug: {{ $module->slug }}</p>
                        </div>
                        <form method="POST" action="{{ route('modules.toggle', $module) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn">{{ $module->is_active ? 'Desactiver' : 'Activer' }}</button>
                        </form>
                    </div>

                    <div style="overflow-x:auto; margin-top: 10px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fonctionnalite</th>
                                    <th>Slug</th>
                                    <th>Etat</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($module->features as $feature)
                                    <tr>
                                        <td>{{ $feature->name }}</td>
                                        <td>{{ $feature->slug }}</td>
                                        <td>
                                            <span class="badge {{ $feature->is_active ? 'badge-success' : 'badge-danger' }}">
                                                {{ $feature->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('modules.features.toggle', $feature) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn">Toggle</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="muted">Aucune fonctionnalite.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <form method="POST" action="{{ route('modules.features.store', $module) }}" style="display:grid; gap:8px; margin-top: 10px;">
                        @csrf
                        <input name="name" type="text" placeholder="Nom fonctionnalite" required class="search" style="max-width:none;" />
                        <input name="description" type="text" placeholder="Description" class="search" style="max-width:none;" />
                        <input name="sort_order" type="number" min="0" placeholder="Ordre" class="search" style="max-width:none;" />
                        <button type="submit" class="btn">Ajouter fonctionnalite</button>
                    </form>
                </div>
            @endforeach
        </div>
    </section>
@endsection
