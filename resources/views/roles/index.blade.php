@extends('layouts.app')

@section('content')
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
