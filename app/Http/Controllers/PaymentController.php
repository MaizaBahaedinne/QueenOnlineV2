<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends MatrixAwareController
{
    public function index()
    {
        $this->enforcePermission('payments', 'list', 'view');

        return view('payments.index', [
            'title' => 'Paiements',
            'payments' => Payment::query()->with(['reservation', 'user'])->latest()->get(),
            'reservations' => Reservation::query()->orderByDesc('id')->get(),
        ]);
    }

    public function create()
    {
        return redirect()->route('payments.index');
    }

    public function store(Request $request)
    {
        $this->enforcePermission('payments', 'create', 'create');

        $validated = $request->validate([
            'reservation_id' => ['required', 'exists:reservations,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'string', 'max:50'],
        ]);

        Payment::create($validated);

        return redirect()->route('payments.index')->with('success', 'Paiement enregistre.');
    }

    public function update(Request $request, Payment $payment)
    {
        $this->enforcePermission('payments', 'update', 'update');

        $validated = $request->validate([
            'reservation_id' => ['required', 'exists:reservations,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['pending', 'paid', 'failed', 'cancelled'])],
            'reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        $payment->update($validated);

        return redirect()->route('payments.index')->with('success', 'Paiement mis a jour.');
    }

    public function destroy(Payment $payment)
    {
        $this->enforcePermission('payments', 'delete', 'delete');

        $payment->delete();

        return redirect()->route('payments.index')->with('success', 'Paiement supprime.');
    }
}
