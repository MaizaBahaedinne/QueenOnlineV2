@extends('layouts.app')

@section('content')
    @php
        $canCreate = auth()->user()?->canFeature('payments', 'create', 'create') ?? false;
        $canUpdate = auth()->user()?->canFeature('payments', 'update', 'update') ?? false;
        $canDelete = auth()->user()?->canFeature('payments', 'delete', 'delete') ?? false;
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
        <h1 class="panel-title">Paiements</h1>
        <p class="panel-sub">Liste par defaut. CRUD en modales.</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        <div style="display:flex; justify-content:flex-end; margin-top:12px;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" data-open-modal="payment-create-modal">Ajouter paiement</button>
            @endif
        </div>

        <div style="overflow-x:auto; margin-top: 12px;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Reservation</th>
                        <th>Montant</th>
                        <th>Methode</th>
                        <th>Statut</th>
                        <th>Date paiement</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $payment->id }}</td>
                            <td>#{{ $payment->reservation_id }}</td>
                            <td>{{ number_format((float) $payment->amount, 2, '.', ' ') }}</td>
                            <td>{{ $payment->method }}</td>
                            <td>{{ $payment->status ?? 'pending' }}</td>
                            <td>@frDateTime($payment->paid_at)</td>
                            <td><div class="action-row">
                                @if ($canUpdate)
                                    <button type="button" class="btn" data-open-modal="payment-edit-modal"
                                        data-payment-id="{{ $payment->id }}"
                                        data-payment-reservation-id="{{ $payment->reservation_id }}"
                                        data-payment-amount="{{ $payment->amount }}"
                                        data-payment-method="{{ $payment->method }}"
                                        data-payment-status="{{ $payment->status ?? 'pending' }}"
                                        data-payment-reference="{{ $payment->reference }}"
                                        data-payment-paid-at="{{ $payment->paid_at ? \Illuminate\Support\Carbon::parse($payment->paid_at)->format('Y-m-d\\TH:i') : '' }}"
                                        data-payment-note="{{ $payment->note }}"
                                    >Modifier</button>
                                @endif
                                @if ($canDelete)
                                    <button type="button" class="btn" data-open-modal="payment-delete-modal"
                                        data-payment-id="{{ $payment->id }}"
                                    >Supprimer</button>
                                @endif
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted">Aucun paiement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canCreate)
        <div class="modal-overlay" id="payment-create-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Ajouter paiement</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" action="{{ route('payments.store') }}" style="display:grid; gap:10px;">@csrf
                <select class="search" style="max-width:none;" name="reservation_id" required><option value="">Reservation</option>@foreach ($reservations as $reservation)<option value="{{ $reservation->id }}">#{{ $reservation->id }}</option>@endforeach</select>
                <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="amount" placeholder="Montant" required>
                <input class="search" style="max-width:none;" type="text" name="method" placeholder="Methode" required>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </form></div></div>
    @endif

    @if ($canUpdate)
        <div class="modal-overlay" id="payment-edit-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Modifier paiement</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" id="payment-edit-form" action="#" style="display:grid; gap:10px;">@csrf @method('PATCH')
                <select class="search" style="max-width:none;" name="reservation_id" id="payment-edit-reservation-id" required><option value="">Reservation</option>@foreach ($reservations as $reservation)<option value="{{ $reservation->id }}">#{{ $reservation->id }}</option>@endforeach</select>
                <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="amount" id="payment-edit-amount" required>
                <input class="search" style="max-width:none;" type="text" name="method" id="payment-edit-method" required>
                <select class="search" style="max-width:none;" name="status" id="payment-edit-status"><option value="pending">En attente</option><option value="paid">Paye</option><option value="failed">Echec</option><option value="cancelled">Annule</option></select>
                <input class="search" style="max-width:none;" type="text" name="reference" id="payment-edit-reference" placeholder="Reference">
                <input class="search" style="max-width:none;" type="datetime-local" name="paid_at" id="payment-edit-paid-at">
                <input class="search" style="max-width:none;" type="text" name="note" id="payment-edit-note" placeholder="Note">
                <button type="submit" class="btn btn-primary">Mettre a jour</button>
            </form></div></div>
    @endif

    @if ($canDelete)
        <div class="modal-overlay" id="payment-delete-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Supprimer paiement</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <p class="panel-sub">Confirmer la suppression de ce paiement ?</p>
            <form method="POST" id="payment-delete-form" action="#" style="margin-top:10px;">@csrf @method('DELETE')
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
                if (modalId === 'payment-edit-modal') {
                    document.getElementById('payment-edit-form').action = `{{ url('payments') }}/${button.dataset.paymentId}`;
                    document.getElementById('payment-edit-reservation-id').value = button.dataset.paymentReservationId ?? '';
                    document.getElementById('payment-edit-amount').value = button.dataset.paymentAmount ?? '';
                    document.getElementById('payment-edit-method').value = button.dataset.paymentMethod ?? '';
                    document.getElementById('payment-edit-status').value = button.dataset.paymentStatus ?? 'pending';
                    document.getElementById('payment-edit-reference').value = button.dataset.paymentReference ?? '';
                    document.getElementById('payment-edit-paid-at').value = button.dataset.paymentPaidAt ?? '';
                    document.getElementById('payment-edit-note').value = button.dataset.paymentNote ?? '';
                }
                if (modalId === 'payment-delete-modal') {
                    document.getElementById('payment-delete-form').action = `{{ url('payments') }}/${button.dataset.paymentId}`;
                }
            });
        });
        closeModalButtons.forEach((button) => button.addEventListener('click', () => { const modal = button.closest('.modal-overlay'); if (modal) closeModal(modal); }));
        document.querySelectorAll('.modal-overlay').forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(modal); }));
    </script>
@endsection
