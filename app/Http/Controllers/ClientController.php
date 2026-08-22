<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
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

        return view('clients.index', [
            'title' => 'Clients',
            'clients' => Client::query()->latest()->get(),
            'governorates' => self::GOVERNORATES,
            'sources' => self::SOURCES,
        ]);
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
}
