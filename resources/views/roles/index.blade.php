@extends('layouts.app')

@section('content')
    @php
        $reservationModules = $modules->filter(function ($module) {
            return in_array($module->slug, ['reservations', 'services-salle', 'staff'], true)
                || in_array($module->slug, ['salles', 'troupe-musicale', 'photographe', 'chanteur', 'notaire', 'animation', 'voiture'], true);
        })->values();
    @endphp

    <style>
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 19, 26, 0.56);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            z-index: 80;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-card {
            width: min(640px, 100%);
            max-height: 88vh;
            overflow: auto;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 16px;
            box-shadow: var(--shadow);
        }

        .modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 10px;
        }

        .modal-title {
            margin: 0;
            font-size: 18px;
        }

        .action-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .permissions-shell {
            display: grid;
            gap: 14px;
            margin-top: 18px;
        }

        .permissions-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .permissions-grid {
            display: grid;
            gap: 14px;
        }

        .permissions-card {
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fff;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .permissions-card-head {
            padding: 14px 16px;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .permissions-card-title {
            margin: 0;
            font-size: 16px;
        }

        .permissions-card-subtitle {
            margin: 4px 0 0;
            color: #607389;
            font-size: 13px;
        }

        .permissions-table-wrap {
            overflow-x: auto;
        }

        .permissions-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .permissions-table th,
        .permissions-table td {
            border-bottom: 1px solid #e8eef5;
            padding: 10px 12px;
            vertical-align: top;
        }

        .permissions-table thead th {
            position: sticky;
            top: 0;
            background: #f8fbff;
            z-index: 1;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #4f687f;
        }

        .permissions-feature-name {
            font-weight: 700;
            color: #173957;
            min-width: 220px;
        }

        .permissions-module-row {
            background: #f3f8fd;
        }

        .permissions-module-row td {
            font-weight: 700;
            color: #214466;
            border-top: 1px solid #dbe7f4;
        }

        .permissions-subgroup {
            margin-top: 8px;
            display: grid;
            gap: 6px;
        }

        .permissions-checkbox-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 10px;
        }

        .permissions-checkbox-grid label {
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: #415a72;
            background: #f8fbff;
            border: 1px solid #dce7f3;
            border-radius: 999px;
            padding: 5px 8px;
        }

        .permissions-service-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #1f5d8f;
            background: #eaf4ff;
            border: 1px solid #cde0f4;
            padding: 4px 8px;
            border-radius: 999px;
        }
    </style>

    <section class="panel">
        <h1 class="panel-title">Gestion des roles utilisateurs</h1>
        <p class="panel-sub">Liste par defaut. CRUD des roles en modales. L'affectation des roles se fait dans la fiche Utilisateurs.</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        @if (session('error'))
            <p class="badge badge-danger" style="margin-top:10px;">{{ session('error') }}</p>
        @endif

        <div style="display:flex; justify-content:flex-end; margin-top:12px;">
            <button type="button" class="btn btn-primary" data-open-modal="role-create-modal">Ajouter role</button>
        </div>

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
                            <td>{{ $role->name }}</td>
                            <td>{{ $role->slug }}</td>
                            <td>{{ $role->description ?: '-' }}</td>
                            <td>{{ $role->users_count }}</td>
                            <td>
                                <div class="action-row">
                                    <button
                                        type="button"
                                        class="btn"
                                        data-open-modal="role-edit-modal"
                                        data-role-id="{{ $role->id }}"
                                        data-role-name="{{ $role->name }}"
                                        data-role-slug="{{ $role->slug }}"
                                        data-role-description="{{ $role->description }}"
                                    >
                                        Modifier
                                    </button>
                                    <button
                                        type="button"
                                        class="btn"
                                        data-open-modal="role-delete-modal"
                                        data-role-id="{{ $role->id }}"
                                        data-role-name="{{ $role->name }}"
                                        data-role-users-count="{{ $role->users_count }}"
                                    >
                                        Supprimer
                                    </button>
                                </div>
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
    </section>

    <section class="panel permissions-shell">
        <div class="permissions-toolbar">
            <div>
                <h2 class="panel-title" style="margin:0;">Matrice des autorisations</h2>
                <p class="panel-sub" style="margin:6px 0 0;">Modifie les autorisations de chaque role. La partie Reservations affiche aussi les sous-autorisations par service.</p>
            </div>
            <a href="{{ route('permissions.matrix') }}" class="btn">Ouvrir la page matrice</a>
        </div>

        <form method="POST" action="{{ route('permissions.matrix.update') }}" class="permissions-grid">
            @csrf

            @foreach ($modules as $module)
                <div class="permissions-card {{ $module->slug === 'reservations' ? 'permissions-reservations-card' : '' }}">
                    <div class="permissions-card-head">
                        <div>
                            <h3 class="permissions-card-title">{{ $module->name }}</h3>
                            <p class="permissions-card-subtitle">{{ $module->description ?: 'Sans description' }}</p>
                        </div>
                        @if ($module->slug === 'reservations')
                            <span class="permissions-service-badge">Sous autorisations reservation</span>
                        @endif
                    </div>

                    <div class="permissions-table-wrap">
                        <table class="permissions-table">
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
                                    <tr class="{{ $module->slug === 'reservations' ? 'permissions-module-row' : '' }}">
                                        <td class="permissions-feature-name">
                                            {{ $feature->name }}
                                            @if ($module->slug === 'reservations')
                                                <div class="permissions-subgroup">
                                                    <span class="permissions-service-badge">Reservations / services</span>
                                                </div>
                                            @endif
                                        </td>
                                        @foreach ($roles as $role)
                                            @php
                                                $key = $role->id . '_' . $feature->id;
                                                $permission = $permissions->get($key);
                                            @endphp
                                            <td>
                                                <div class="permissions-checkbox-grid">
                                                    <label><input type="checkbox" name="matrix[{{ $role->id }}][{{ $feature->id }}][can_view]" {{ $permission?->can_view ? 'checked' : '' }}> view</label>
                                                    <label><input type="checkbox" name="matrix[{{ $role->id }}][{{ $feature->id }}][can_create]" {{ $permission?->can_create ? 'checked' : '' }}> create</label>
                                                    <label><input type="checkbox" name="matrix[{{ $role->id }}][{{ $feature->id }}][can_update]" {{ $permission?->can_update ? 'checked' : '' }}> update</label>
                                                    <label><input type="checkbox" name="matrix[{{ $role->id }}][{{ $feature->id }}][can_delete]" {{ $permission?->can_delete ? 'checked' : '' }}> delete</label>
                                                </div>
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

    <div class="modal-overlay" id="role-create-modal">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="modal-title">Ajouter role</h3>
                <button type="button" class="btn" data-close-modal>Fermer</button>
            </div>
            <form method="POST" action="{{ route('roles.store') }}" style="display:grid; gap:10px;">
                @csrf
                <input class="search" style="max-width:none;" type="text" name="name" placeholder="Nom du role" required>
                <input class="search" style="max-width:none;" type="text" name="slug" placeholder="Slug (ex: superviseur)" required>
                <input class="search" style="max-width:none;" type="text" name="description" placeholder="Description">
                <button class="btn btn-primary" type="submit">Ajouter</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="role-edit-modal">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="modal-title">Modifier role</h3>
                <button type="button" class="btn" data-close-modal>Fermer</button>
            </div>
            <form method="POST" id="role-edit-form" action="#" style="display:grid; gap:10px;">
                @csrf
                @method('PATCH')
                <input class="search" style="max-width:none;" type="text" name="name" id="role-edit-name" required>
                <input class="search" style="max-width:none;" type="text" name="slug" id="role-edit-slug" required>
                <input class="search" style="max-width:none;" type="text" name="description" id="role-edit-description" placeholder="Description">
                <button class="btn btn-primary" type="submit">Enregistrer</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="role-delete-modal">
        <div class="modal-card">
            <div class="modal-head">
                <h3 class="modal-title">Supprimer role</h3>
                <button type="button" class="btn" data-close-modal>Fermer</button>
            </div>
            <p id="role-delete-text" class="panel-sub"></p>
            <p id="role-delete-hint" class="panel-sub" style="margin-top:8px;"></p>
            <form method="POST" id="role-delete-form" action="#" style="margin-top:10px;">
                @csrf
                @method('DELETE')
                <button class="btn" type="submit">Confirmer suppression</button>
            </form>
        </div>
    </div>

    <script>
        const openModalButtons = document.querySelectorAll('[data-open-modal]');
        const closeModalButtons = document.querySelectorAll('[data-close-modal]');

        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('show');
            }
        }

        function closeModal(modal) {
            modal.classList.remove('show');
        }

        openModalButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const modalId = button.getAttribute('data-open-modal');
                openModal(modalId);

                if (modalId === 'role-edit-modal') {
                    document.getElementById('role-edit-form').action = `{{ url('roles') }}/${button.dataset.roleId}`;
                    document.getElementById('role-edit-name').value = button.dataset.roleName ?? '';
                    document.getElementById('role-edit-slug').value = button.dataset.roleSlug ?? '';
                    document.getElementById('role-edit-description').value = button.dataset.roleDescription ?? '';
                }

                if (modalId === 'role-delete-modal') {
                    document.getElementById('role-delete-form').action = `{{ url('roles') }}/${button.dataset.roleId}`;
                    document.getElementById('role-delete-text').textContent = `Confirmer la suppression du role "${button.dataset.roleName}" ?`;
                    const usersCount = Number(button.dataset.roleUsersCount || 0);
                    document.getElementById('role-delete-hint').textContent = usersCount > 0
                        ? 'Ce role est affecte a des utilisateurs: suppression bloquee tant que ces affectations existent.'
                        : '';
                }
            });
        });

        closeModalButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const modal = button.closest('.modal-overlay');
                if (modal) {
                    closeModal(modal);
                }
            });
        });

        document.querySelectorAll('.modal-overlay').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal(modal);
                }
            });
        });
    </script>
@endsection
