@extends('layouts.app')

@section('content')
    @php
        $canCreate = auth()->user()?->canFeature('users', 'create', 'create') ?? false;
        $canUpdate = auth()->user()?->canFeature('users', 'update', 'update') ?? false;
        $canDelete = auth()->user()?->canFeature('users', 'delete', 'delete') ?? false;
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
            width: min(700px, 100%);
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
        <h1 class="panel-title">Utilisateurs</h1>
        <p class="panel-sub">Liste par defaut. Les actions CRUD sont executees via modales. L'affectation du role se fait ici.</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        <div style="display:flex; justify-content:flex-end; margin-top:12px;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" data-open-modal="user-create-modal">Ajouter utilisateur</button>
            @endif
        </div>

        <div style="overflow-x:auto; margin-top: 12px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Telephone</th>
                        <th>Role</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? '-' }}</td>
                            <td>{{ $user->role?->name ?? '-' }}</td>
                            <td>{{ $user->status ?? 'active' }}</td>
                            <td>
                                <div class="action-row">
                                    @if ($canUpdate)
                                        <button
                                            type="button"
                                            class="btn"
                                            data-open-modal="user-edit-modal"
                                            data-user-id="{{ $user->id }}"
                                            data-user-name="{{ $user->name }}"
                                            data-user-email="{{ $user->email }}"
                                            data-user-phone="{{ $user->phone }}"
                                            data-user-status="{{ $user->status ?? 'active' }}"
                                            data-user-role-id="{{ $user->role_id }}"
                                        >
                                            Modifier
                                        </button>
                                    @endif
                                    @if ($canDelete)
                                        <button
                                            type="button"
                                            class="btn"
                                            data-open-modal="user-delete-modal"
                                            data-user-id="{{ $user->id }}"
                                            data-user-name="{{ $user->name }}"
                                        >
                                            Supprimer
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">Aucun utilisateur.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canCreate)
        <div class="modal-overlay" id="user-create-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Ajouter utilisateur</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>
                <form method="POST" action="{{ route('users.store') }}" style="display:grid; gap:10px;">
                    @csrf
                    <input class="search" style="max-width:none;" type="text" name="name" placeholder="Nom" required>
                    <input class="search" style="max-width:none;" type="email" name="email" placeholder="Email" required>
                    <input class="search" style="max-width:none;" type="password" name="password" placeholder="Mot de passe" required>
                    <input class="search" style="max-width:none;" type="text" name="phone" placeholder="Telephone">
                    <select class="search" style="max-width:none;" name="role_id">
                        <option value="">Aucun role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <select class="search" style="max-width:none;" name="status">
                        <option value="active">Actif</option>
                        <option value="inactive">Inactif</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </form>
            </div>
        </div>
    @endif

    @if ($canUpdate)
        <div class="modal-overlay" id="user-edit-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Modifier utilisateur</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>
                <form method="POST" id="user-edit-form" action="#" style="display:grid; gap:10px;">
                    @csrf
                    @method('PATCH')
                    <input class="search" style="max-width:none;" type="text" name="name" id="user-edit-name" required>
                    <input class="search" style="max-width:none;" type="email" name="email" id="user-edit-email" required>
                    <input class="search" style="max-width:none;" type="password" name="password" placeholder="Nouveau mot de passe (optionnel)">
                    <input class="search" style="max-width:none;" type="text" name="phone" id="user-edit-phone" placeholder="Telephone">
                    <select class="search" style="max-width:none;" name="role_id" id="user-edit-role-id">
                        <option value="">Aucun role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <select class="search" style="max-width:none;" name="status" id="user-edit-status">
                        <option value="active">Actif</option>
                        <option value="inactive">Inactif</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Mettre a jour</button>
                </form>
            </div>
        </div>
    @endif

    @if ($canDelete)
        <div class="modal-overlay" id="user-delete-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Supprimer utilisateur</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>
                <p id="user-delete-text" class="panel-sub"></p>
                <form method="POST" id="user-delete-form" action="#" style="margin-top:10px;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn">Confirmer suppression</button>
                </form>
            </div>
        </div>
    @endif

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

                if (modalId === 'user-edit-modal') {
                    document.getElementById('user-edit-form').action = `{{ url('users') }}/${button.dataset.userId}`;
                    document.getElementById('user-edit-name').value = button.dataset.userName ?? '';
                    document.getElementById('user-edit-email').value = button.dataset.userEmail ?? '';
                    document.getElementById('user-edit-phone').value = button.dataset.userPhone ?? '';
                    document.getElementById('user-edit-status').value = button.dataset.userStatus ?? 'active';
                    document.getElementById('user-edit-role-id').value = button.dataset.userRoleId ?? '';
                }

                if (modalId === 'user-delete-modal') {
                    document.getElementById('user-delete-form').action = `{{ url('users') }}/${button.dataset.userId}`;
                    document.getElementById('user-delete-text').textContent = `Confirmer la suppression de "${button.dataset.userName}" ?`;
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
