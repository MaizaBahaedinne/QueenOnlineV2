@extends('layouts.app')

@section('content')
    @php
        $canCreate = auth()->user()?->canFeature('reservations', 'create', 'create') ?? false;
        $canUpdate = auth()->user()?->canFeature('reservations', 'update', 'update') ?? false;
        $canDelete = auth()->user()?->canFeature('reservations', 'delete', 'delete') ?? false;
    @endphp

    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 19, 26, 0.56); display: none; align-items: center; justify-content: center; padding: 18px; z-index: 80; }
        .modal-overlay.show { display: flex; }
        .modal-card { width: min(760px, 100%); max-height: 88vh; overflow: auto; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 16px; box-shadow: var(--shadow); }
        .modal-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
        .modal-title { margin: 0; font-size: 18px; }
        .action-row { display: flex; gap: 8px; flex-wrap: wrap; }
    </style>

    <section class="panel">
        <h1 class="panel-title">Reservations</h1>
        <p class="panel-sub">Liste par defaut. CRUD en modales.</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        <div style="display:flex; justify-content:flex-end; margin-top:12px;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" data-open-modal="reservation-create-modal">Ajouter reservation</button>
            @endif
        </div>

        <div style="overflow-x:auto; margin-top: 12px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Salle</th>
                        <th>Debut</th>
                        <th>Fin</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reservations as $reservation)
                        <tr>
                            <td>{{ $reservation->id }}</td>
                            <td>{{ $reservation->client?->name ?? '-' }}</td>
                            <td>{{ $reservation->salle?->name ?? '-' }}</td>
                            <td>{{ $reservation->start_date }}</td>
                            <td>{{ $reservation->end_date }}</td>
                            <td>{{ $reservation->status ?? 'pending' }}</td>
                            <td><div class="action-row">
                                @if ($canUpdate)
                                    <button type="button" class="btn" data-open-modal="reservation-edit-modal"
                                        data-reservation-id="{{ $reservation->id }}"
                                        data-reservation-client-id="{{ $reservation->client_id }}"
                                        data-reservation-salle-id="{{ $reservation->salle_id }}"
                                        data-reservation-start-date="{{ $reservation->start_date }}"
                                        data-reservation-end-date="{{ $reservation->end_date }}"
                                        data-reservation-status="{{ $reservation->status ?? 'pending' }}"
                                        data-reservation-total-amount="{{ $reservation->total_amount }}"
                                    >Modifier</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="btn" data-open-modal="reservation-delete-modal"
                                        data-reservation-id="{{ $reservation->id }}"
                                    >Supprimer</button>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">Aucune reservation.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canCreate)
        <div class="modal-overlay" id="reservation-create-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Ajouter reservation</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" action="{{ route('reservations.store') }}" style="display:grid; gap:10px;">@csrf
                <select class="search" style="max-width:none;" name="client_id" required><option value="">Client</option>@foreach ($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select>
                <select class="search" style="max-width:none;" name="salle_id" required><option value="">Salle</option>@foreach ($salles as $salle)<option value="{{ $salle->id }}">{{ $salle->name }}</option>@endforeach</select>
                <input class="search" style="max-width:none;" type="date" name="start_date" required>
                <input class="search" style="max-width:none;" type="date" name="end_date" required>
                <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="total_amount" placeholder="Montant total">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form></div></div>
    @endif

    @if ($canUpdate)
        <div class="modal-overlay" id="reservation-edit-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Modifier reservation</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" id="reservation-edit-form" action="#" style="display:grid; gap:10px;">@csrf @method('PATCH')
                <select class="search" style="max-width:none;" name="client_id" id="reservation-edit-client-id" required><option value="">Client</option>@foreach ($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select>
                <select class="search" style="max-width:none;" name="salle_id" id="reservation-edit-salle-id" required><option value="">Salle</option>@foreach ($salles as $salle)<option value="{{ $salle->id }}">{{ $salle->name }}</option>@endforeach</select>
                <input class="search" style="max-width:none;" type="date" name="start_date" id="reservation-edit-start-date" required>
                <input class="search" style="max-width:none;" type="date" name="end_date" id="reservation-edit-end-date" required>
                <select class="search" style="max-width:none;" name="status" id="reservation-edit-status"><option value="pending">En attente</option><option value="confirmed">Confirmee</option><option value="cancelled">Annulee</option><option value="completed">Terminee</option></select>
                <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="total_amount" id="reservation-edit-total-amount">
                <button type="submit" class="btn btn-primary">Mettre a jour</button>
            </form></div></div>
    @endif

    @if ($canDelete)
        <div class="modal-overlay" id="reservation-delete-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Supprimer reservation</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <p id="reservation-delete-text" class="panel-sub">Confirmer la suppression de cette reservation ?</p>
            <form method="POST" id="reservation-delete-form" action="#" style="margin-top:10px;">@csrf @method('DELETE')
                <button type="submit" class="btn">Confirmer suppression</button>
            </form></div></div>
    @endif

    <script>
        const openModalButtons = document.querySelectorAll('[data-open-modal]');
        const closeModalButtons = document.querySelectorAll('[data-close-modal]');
        const openModal = (id) => { const m = document.getElementById(id); if (m) m.classList.add('show'); };
        const closeModal = (m) => m.classList.remove('show');
        openModalButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const modalId = button.getAttribute('data-open-modal');
                openModal(modalId);
                if (modalId === 'reservation-edit-modal') {
                    document.getElementById('reservation-edit-form').action = `{{ url('reservations') }}/${button.dataset.reservationId}`;
                    document.getElementById('reservation-edit-client-id').value = button.dataset.reservationClientId ?? '';
                    document.getElementById('reservation-edit-salle-id').value = button.dataset.reservationSalleId ?? '';
                    document.getElementById('reservation-edit-start-date').value = button.dataset.reservationStartDate ?? '';
                    document.getElementById('reservation-edit-end-date').value = button.dataset.reservationEndDate ?? '';
                    document.getElementById('reservation-edit-status').value = button.dataset.reservationStatus ?? 'pending';
                    document.getElementById('reservation-edit-total-amount').value = button.dataset.reservationTotalAmount ?? '';
                }
                if (modalId === 'reservation-delete-modal') {
                    document.getElementById('reservation-delete-form').action = `{{ url('reservations') }}/${button.dataset.reservationId}`;
                }
            });
        });
        closeModalButtons.forEach((button) => button.addEventListener('click', () => { const modal = button.closest('.modal-overlay'); if (modal) closeModal(modal); }));
        document.querySelectorAll('.modal-overlay').forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(modal); }));
    </script>
@endsection
