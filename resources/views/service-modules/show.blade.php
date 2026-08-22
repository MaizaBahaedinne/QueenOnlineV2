@extends('layouts.app')

@section('content')
    <section class="panel">
        <h1 class="panel-title">{{ $moduleMeta['name'] }}</h1>
        <p class="panel-sub">Module metier operationnel (donnees + CRUD).</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        <div class="content-grid" style="margin-top: 14px;">
            <div class="panel" style="box-shadow:none;">
                <h2 class="panel-title">Nouveau element</h2>
                <form method="POST" action="{{ route('service-modules.items.store', $moduleSlug) }}" style="display:grid; gap:10px; margin-top:8px;">
                    @csrf
                    <input class="search" style="max-width:none;" type="text" name="name" placeholder="Nom" required>
                    <input class="search" style="max-width:none;" type="text" name="phone" placeholder="Telephone">
                    <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="base_price" placeholder="Prix de base">
                    <select class="search" style="max-width:none;" name="status" required>
                        <option value="active">Actif</option>
                        <option value="inactive">Inactif</option>
                    </select>
                    <input class="search" style="max-width:none;" type="text" name="notes" placeholder="Notes">
                    <button type="submit" class="btn btn-primary" style="width:max-content;">Ajouter</button>
                </form>
            </div>

            <div class="panel" style="box-shadow:none;">
                <h2 class="panel-title">Elements</h2>
                <div style="overflow-x:auto; margin-top:10px;">
                    <table>
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Telephone</th>
                                <th>Prix base</th>
                                <th>Statut</th>
                                <th>Notes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->phone ?? '-' }}</td>
                                    <td>{{ number_format((float) $item->base_price, 2, '.', ' ') }}</td>
                                    <td>{{ $item->status }}</td>
                                    <td>{{ $item->notes ?? '-' }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('service-modules.items.update', [$moduleSlug, $item]) }}" style="display:grid; gap:6px; min-width:220px;">
                                            @csrf
                                            @method('PATCH')
                                            <input class="search" style="max-width:none;" type="text" name="name" value="{{ $item->name }}" required>
                                            <input class="search" style="max-width:none;" type="text" name="phone" value="{{ $item->phone }}">
                                            <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="base_price" value="{{ $item->base_price }}">
                                            <select class="search" style="max-width:none;" name="status" required>
                                                <option value="active" {{ $item->status === 'active' ? 'selected' : '' }}>Actif</option>
                                                <option value="inactive" {{ $item->status === 'inactive' ? 'selected' : '' }}>Inactif</option>
                                            </select>
                                            <input class="search" style="max-width:none;" type="text" name="notes" value="{{ $item->notes }}">
                                            <button type="submit" class="btn">Mettre a jour</button>
                                        </form>
                                        <form method="POST" action="{{ route('service-modules.items.destroy', [$moduleSlug, $item]) }}" onsubmit="return confirm('Supprimer cet element ?');" style="margin-top:8px;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="muted">Aucun element.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($moduleMeta['packs'])
                <div class="panel" style="box-shadow:none;">
                    <h2 class="panel-title">Packs</h2>
                    <form method="POST" action="{{ route('service-modules.packs.store', $moduleSlug) }}" style="display:grid; gap:10px; margin:8px 0 12px;">
                        @csrf
                        <select class="search" style="max-width:none;" name="service_module_item_id">
                            <option value="">Sans element parent</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                        <input class="search" style="max-width:none;" type="text" name="name" placeholder="Nom pack" required>
                        <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="price" placeholder="Prix pack">
                        <select class="search" style="max-width:none;" name="status" required>
                            <option value="active">Actif</option>
                            <option value="inactive">Inactif</option>
                        </select>
                        <input class="search" style="max-width:none;" type="text" name="description" placeholder="Description pack">
                        <button type="submit" class="btn">Ajouter pack</button>
                    </form>

                    <div style="overflow-x:auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Pack</th>
                                    <th>Element lie</th>
                                    <th>Prix</th>
                                    <th>Statut</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($packs as $pack)
                                    <tr>
                                        <td>{{ $pack->name }}</td>
                                        <td>{{ $pack->item?->name ?? '-' }}</td>
                                        <td>{{ number_format((float) $pack->price, 2, '.', ' ') }}</td>
                                        <td>{{ $pack->status }}</td>
                                        <td>{{ $pack->description ?? '-' }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('service-modules.packs.update', [$moduleSlug, $pack]) }}" style="display:grid; gap:6px; min-width:220px;">
                                                @csrf
                                                @method('PATCH')
                                                <select class="search" style="max-width:none;" name="service_module_item_id">
                                                    <option value="">Sans element parent</option>
                                                    @foreach ($items as $item)
                                                        <option value="{{ $item->id }}" {{ $pack->service_module_item_id === $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                                    @endforeach
                                                </select>
                                                <input class="search" style="max-width:none;" type="text" name="name" value="{{ $pack->name }}" required>
                                                <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="price" value="{{ $pack->price }}">
                                                <select class="search" style="max-width:none;" name="status" required>
                                                    <option value="active" {{ $pack->status === 'active' ? 'selected' : '' }}>Actif</option>
                                                    <option value="inactive" {{ $pack->status === 'inactive' ? 'selected' : '' }}>Inactif</option>
                                                </select>
                                                <input class="search" style="max-width:none;" type="text" name="description" value="{{ $pack->description }}">
                                                <button type="submit" class="btn">Mettre a jour</button>
                                            </form>
                                            <form method="POST" action="{{ route('service-modules.packs.destroy', [$moduleSlug, $pack]) }}" onsubmit="return confirm('Supprimer ce pack ?');" style="margin-top:8px;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn">Supprimer</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="muted">Aucun pack.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
