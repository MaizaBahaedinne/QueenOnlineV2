<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientCreditLedger;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ClientController extends MatrixAwareController
{
    private const GOVERNORATES = [
        'Ariana',
        'Beja',
        'Ben Arous',
        'Bizerte',
        'Gabes',
        'Gafsa',
        'Jendouba',
        'Kairouan',
        'Kasserine',
        'Kebili',
        'Le Kef',
        'Mahdia',
        'La Manouba',
        'Medenine',
        'Monastir',
        'Nabeul',
        'Sfax',
        'Sidi Bouzid',
        'Siliana',
        'Sousse',
        'Tataouine',
        'Tozeur',
        'Tunis',
        'Zaghouan',
    ];

    private const SOURCES = [
        'passager',
        'reseaux-sociaux-web',
        'presence-event',
        'recommandation',
        'connaissance-queenpark',
    ];

    public function index()
    {
        $this->enforcePermission('clients', 'list', 'view');

        $clients = Client::query()->latest()->get();
        $balances = [];
        foreach ($clients as $client) {
            $balances[$client->id] = $this->getClientCreditBalance((int) $client->id);
        }

        return view('clients.index', [
            'title' => 'Clients',
            'clients' => $clients,
            'clientCreditBalances' => $balances,
            'governorates' => self::GOVERNORATES,
            'sources' => self::SOURCES,
        ]);
    }

    public function show(Client $client)
    {
        $this->enforcePermission('clients', 'list', 'view');

        $client->load('reservations.salle');

        $reservations = Reservation::query()
            ->with(['salle'])
            ->where('client_id', $client->id)
            ->latest('start_date')
            ->latest('id')
            ->get();

        $payments = Payment::query()
            ->with(['reservation:id,title,start_date,end_date', 'user:id,name'])
            ->whereHas('reservation', function ($query) use ($client) {
                $query->where('client_id', $client->id);
            })
            ->latest('paid_at')
            ->latest('id')
            ->get();

        $otherClients = Client::query()
            ->where('id', '!=', $client->id)
            ->orderBy('name')
            ->get(['id', 'first_name', 'name', 'cin']);

        return view('clients.show', [
            'title' => 'Profil client',
            'client' => $client,
            'reservations' => $reservations,
            'payments' => $payments,
            'clientCreditBalance' => $this->getClientCreditBalance((int) $client->id),
            'otherClients' => $otherClients,
        ]);
    }

    public function transferCredit(Request $request, Client $client)
    {
        $this->enforcePermission('clients', 'update', 'update');

        if (! Schema::hasTable('client_credit_ledgers')) {
            return redirect()->route('clients.index')->withErrors([
                'client_transfer' => 'Module transfert de solde indisponible. Lance les migrations.',
            ]);
        }

        $validated = $request->validate([
            'target_client_id' => ['required', 'integer', 'exists:clients,id', 'different:' . $client->id],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string'],
        ]);

        $amount = (float) $validated['amount'];
        $sourceBalance = $this->getClientCreditBalance((int) $client->id);

        if ($amount > $sourceBalance) {
            return redirect()->route('clients.index')->withErrors([
                'client_transfer_amount' => 'Le montant depasse le solde disponible du client source.',
            ])->withInput();
        }

        $targetClientId = (int) $validated['target_client_id'];

        DB::transaction(function () use ($client, $validated, $amount, $targetClientId): void {
            ClientCreditLedger::query()->create([
                'client_id' => $client->id,
                'reservation_id' => null,
                'user_id' => Auth::id(),
                'type' => 'transfer_out',
                'amount' => $amount,
                'related_client_id' => $targetClientId,
                'description' => $validated['note'] ?? ('Transfert sortant vers client #' . $targetClientId),
            ]);

            ClientCreditLedger::query()->create([
                'client_id' => $targetClientId,
                'reservation_id' => null,
                'user_id' => Auth::id(),
                'type' => 'transfer_in',
                'amount' => $amount,
                'related_client_id' => $client->id,
                'description' => $validated['note'] ?? ('Transfert entrant depuis client #' . $client->id),
            ]);
        });

        return redirect()->back()->with('success', 'Transfert de solde enregistre avec succes.');
    }

    public function create()
    {
        return redirect()->route('clients.index');
    }

    public function store(Request $request)
    {
        $this->enforcePermission('clients', 'create', 'create');

        $validated = $this->validateClientPayload($request);
        $payload = $this->normalizeClientPayload($validated);
        $payload['status'] = 'active';

        Client::create($payload);

        return redirect()->route('clients.index')->with('success', 'Client cree.');
    }

    public function update(Request $request, Client $client)
    {
        $this->enforcePermission('clients', 'update', 'update');

        $validated = $this->validateClientPayload($request, $client->id);

        $client->update($this->normalizeClientPayload($validated));

        return redirect()->route('clients.index')->with('success', 'Client mis a jour.');
    }

    public function checkCin(Request $request)
    {
        $cin = trim((string) $request->query('cin', ''));
        $ignoreId = $request->query('ignore_id');

        if ($cin === '') {
            return response()->json([
                'exists' => false,
                'message' => null,
            ]);
        }

        $query = Client::query()->where('cin', $cin);
        if (! empty($ignoreId)) {
            $query->where('id', '!=', (int) $ignoreId);
        }

        $exists = $query->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Ce CIN existe deja dans la base.' : null,
        ]);
    }

    public function destroy(Client $client)
    {
        $this->enforcePermission('clients', 'delete', 'delete');

        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client supprime.');
    }

    private function validateClientPayload(Request $request, ?int $ignoreId = null): array
    {
        $hasExtendedColumns = Schema::hasColumn('clients', 'client_type')
            && Schema::hasColumn('clients', 'first_name')
            && Schema::hasColumn('clients', 'governorate');

        if (! $hasExtendedColumns) {
            return $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email'],
                'phone' => ['required', 'string', 'max:50'],
                'cin' => ['required', 'regex:/^[0-9]{8}$/', Rule::unique('clients', 'cin')->ignore($ignoreId)],
                'date_cin' => ['nullable', 'date'],
                'city' => ['nullable', 'string', 'max:255'],
            ]);
        }

        return $request->validate([
            'client_type' => ['required', Rule::in(['personne-physique', 'societe'])],
            'fiscal_number' => ['nullable', 'string', 'max:100', 'required_if:client_type,societe'],
            'company_name' => ['nullable', 'string', 'max:255', 'required_if:client_type,societe'],

            'first_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'cin' => ['required', 'regex:/^[0-9]{8}$/', Rule::unique('clients', 'cin')->ignore($ignoreId)],
            'date_cin' => ['nullable', 'date'],
            'email' => ['nullable', 'email'],

            'address_number' => ['nullable', 'string', 'max:50'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'governorate' => ['required', Rule::in(self::GOVERNORATES)],

            'phone' => ['required', 'string', 'max:50'],
            'phone_label_1' => ['nullable', 'string', 'max:100'],
            'phone_2' => ['nullable', 'string', 'max:50'],
            'phone_label_2' => ['nullable', 'string', 'max:100'],

            'source' => ['required', Rule::in(self::SOURCES)],
            'note' => ['nullable', 'string'],
        ]);
    }

    private function normalizeClientPayload(array $validated): array
    {
        if (($validated['client_type'] ?? null) === 'personne-physique') {
            $validated['fiscal_number'] = null;
            $validated['company_name'] = null;
        }

        return $validated;
    }

    private function getClientCreditBalance(int $clientId): float
    {
        if (! Schema::hasTable('client_credit_ledgers')) {
            return 0.0;
        }

        $credit = (float) ClientCreditLedger::query()
            ->where('client_id', $clientId)
            ->whereIn('type', ['credit', 'transfer_in'])
            ->sum('amount');

        $debit = (float) ClientCreditLedger::query()
            ->where('client_id', $clientId)
            ->whereIn('type', ['debit', 'transfer_out'])
            ->sum('amount');

        return max($credit - $debit, 0);
    }
}
