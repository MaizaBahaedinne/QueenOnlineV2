@extends('layouts.app')

@section('content')
    <section class="panel">
        <h1 class="panel-title">Paiements</h1>
        <p class="panel-sub">Historique des encaissements.</p>

        <div class="page-actions">
            <a class="btn" href="{{ route('dashboard') }}">Retour dashboard</a>
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
                            <td>{{ $payment->paid_at ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">Aucun paiement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
