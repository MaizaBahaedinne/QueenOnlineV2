@extends('layouts.app')

@section('content')
    <section class="panel">
        <h1 class="panel-title">Matrice des autorisations</h1>
        <p class="panel-sub">Modifie les permissions par role utilisateur et fonctionnalite.</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top: 10px;">{{ session('success') }}</p>
        @endif

        <form method="POST" action="{{ route('permissions.matrix.update') }}" style="margin-top: 14px;">
            @csrf

            @foreach ($modules as $module)
                <div class="panel" style="box-shadow:none; margin-bottom:12px;">
                    <h2 class="panel-title" style="margin:0;">{{ $module->name }}</h2>
                    <p class="panel-sub">{{ $module->description ?: 'Sans description' }}</p>

                    <div style="overflow-x:auto; margin-top: 10px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Fonctionnalite</th>
                                    @foreach ($roles as $role)
                                        <th>{{ $role->name }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($module->features as $feature)
                                    <tr>
                                        <td>{{ $feature->name }}</td>
                                        @foreach ($roles as $role)
                                            @php
                                                $key = $role->id . '_' . $feature->id;
                                                $permission = $permissions->get($key);
                                            @endphp
                                            <td>
                                                <label style="display:block; font-size:12px;"><input type="checkbox" name="matrix[{{ $role->id }}][{{ $feature->id }}][can_view]" {{ $permission?->can_view ? 'checked' : '' }}> view</label>
                                                <label style="display:block; font-size:12px;"><input type="checkbox" name="matrix[{{ $role->id }}][{{ $feature->id }}][can_create]" {{ $permission?->can_create ? 'checked' : '' }}> create</label>
                                                <label style="display:block; font-size:12px;"><input type="checkbox" name="matrix[{{ $role->id }}][{{ $feature->id }}][can_update]" {{ $permission?->can_update ? 'checked' : '' }}> update</label>
                                                <label style="display:block; font-size:12px;"><input type="checkbox" name="matrix[{{ $role->id }}][{{ $feature->id }}][can_delete]" {{ $permission?->can_delete ? 'checked' : '' }}> delete</label>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary">Enregistrer la matrice</button>
        </form>
    </section>
@endsection
