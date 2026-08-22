<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends MatrixAwareController
{
    public function index()
    {
        $this->enforcePermission('payments', 'list', 'view');

        return view('payments.index', [
            'payments' => Payment::query()->with(['reservation', 'user'])->latest()->get(),
        ]);
    }

    public function create()
    {
        $this->enforcePermission('payments', 'create', 'create');

        return view('payments.create');
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

        return redirect()->route('payments.index')->with('success', 'Paiement enregistré.');
    }
}
