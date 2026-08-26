<?php

namespace App\Http\Controllers;

use App\Models\MigrationMapping;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MigrationMappingController extends MatrixAwareController
{
    private const DEFAULT_SOURCE_CONNECTION = 'legacy';
    private const DEFAULT_TARGET_CONNECTION = 'mysql';

    private function allowedConnections(): array
    {
        $names = array_keys((array) config('database.connections', []));

        return array_values(array_filter($names, function (string $name): bool {
            return in_array($name, ['mysql', 'legacy', 'mariadb'], true);
        }));
    }

    private function resolveConnection(string $requested, array $allowed): string
    {
        if (in_array($requested, $allowed, true)) {
            return $requested;
        }

        if (in_array(self::DEFAULT_TARGET_CONNECTION, $allowed, true)) {
            return self::DEFAULT_TARGET_CONNECTION;
        }

        return $allowed[0] ?? 'mysql';
    }

    public function index(Request $request)
    {
        $this->enforcePermission('reservations', 'list', 'view');

        $sourceTableFilter = trim((string) $request->query('source_table', ''));

        $query = MigrationMapping::query();
        if ($sourceTableFilter !== '') {
            $query->where('source_table', $sourceTableFilter);
        }

        $rows = $query
            ->orderBy('source_table')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $allowedConnections = $this->allowedConnections();
        $defaultSourceConnection = $this->resolveConnection(self::DEFAULT_SOURCE_CONNECTION, $allowedConnections);
        $defaultTargetConnection = $this->resolveConnection(self::DEFAULT_TARGET_CONNECTION, $allowedConnections);

        return view('migration-mappings.index', [
            'title' => 'Mapping migration',
            'mappings' => $rows,
            'sourceTableFilter' => $sourceTableFilter,
            'sourceTables' => MigrationMapping::query()->select('source_table')->distinct()->orderBy('source_table')->pluck('source_table'),
            'dbConnections' => $allowedConnections,
            'defaultSourceConnection' => $defaultSourceConnection,
            'defaultTargetConnection' => $defaultTargetConnection,
        ]);
    }

    public function store(Request $request)
    {
        $this->enforcePermission('reservations', 'create', 'create');

        $validated = $request->validate([
            'source_table' => ['required', 'string', 'max:150'],
            'source_column' => ['required', 'string', 'max:150'],
            'target_table' => ['required', 'string', 'max:150'],
            'target_column' => ['required', 'string', 'max:150'],
            'condition_value' => ['nullable', 'string', 'max:255'],
            'signification' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'in:0,1'],
        ]);

        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_active'] = ((string) ($validated['is_active'] ?? '1')) === '1';

        MigrationMapping::query()->create($validated);

        return redirect()->route('migration-mappings.index')->with('success', 'Ligne de mapping ajoutee.');
    }

    public function update(Request $request, MigrationMapping $migrationMapping)
    {
        $this->enforcePermission('reservations', 'update', 'update');

        $validated = $request->validate([
            'source_table' => ['required', 'string', 'max:150'],
            'source_column' => ['required', 'string', 'max:150'],
            'target_table' => ['required', 'string', 'max:150'],
            'target_column' => ['required', 'string', 'max:150'],
            'condition_value' => ['nullable', 'string', 'max:255'],
            'signification' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'in:0,1'],
        ]);

        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['is_active'] = ((string) ($validated['is_active'] ?? '1')) === '1';

        $migrationMapping->update($validated);

        return redirect()->route('migration-mappings.index')->with('success', 'Ligne de mapping mise a jour.');
    }

    public function destroy(MigrationMapping $migrationMapping)
    {
        $this->enforcePermission('reservations', 'delete', 'delete');

        $migrationMapping->delete();

        return redirect()->route('migration-mappings.index')->with('success', 'Ligne de mapping supprimee.');
    }

    public function export(Request $request)
    {
        $this->enforcePermission('reservations', 'list', 'view');

        $format = trim((string) $request->query('format', 'csv'));
        $sourceTable = trim((string) $request->query('source_table', ''));

        $query = MigrationMapping::query()->orderBy('source_table')->orderBy('sort_order')->orderBy('id');
        if ($sourceTable !== '') {
            $query->where('source_table', $sourceTable);
        }

        $rows = $query->get([
            'source_table',
            'source_column',
            'target_table',
            'target_column',
            'condition_value',
            'signification',
            'sort_order',
            'is_active',
        ]);

        if ($format === 'json') {
            $payload = [
                'generated_at' => now()->toDateTimeString(),
                'source_table_filter' => $sourceTable !== '' ? $sourceTable : null,
                'rows' => $rows,
            ];

            $name = 'migration_mapping_' . now()->format('Ymd_His') . '.json';

            return Response::make(
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                200,
                [
                    'Content-Type' => 'application/json',
                    'Content-Disposition' => 'attachment; filename="' . $name . '"',
                ]
            );
        }

        $name = 'migration_mapping_' . now()->format('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            if (! $out) {
                return;
            }

            fputcsv($out, [
                'source_table',
                'source_column',
                'target_table',
                'target_column',
                'condition_value',
                'signification',
                'sort_order',
                'is_active',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->source_table,
                    $row->source_column,
                    $row->target_table,
                    $row->target_column,
                    $row->condition_value,
                    $row->signification,
                    $row->sort_order,
                    $row->is_active ? '1' : '0',
                ]);
            }

            fclose($out);
        }, $name, $headers);
    }

    public function schemaTables(Request $request)
    {
        $this->enforcePermission('reservations', 'list', 'view');

        $allowedConnections = $this->allowedConnections();
        $validated = $request->validate([
            'connection' => ['required', Rule::in($allowedConnections)],
        ]);

        try {
            $tables = collect(Schema::connection($validated['connection'])->getTableListing())
                ->map(fn ($value) => (string) $value)
                ->sort()
                ->values()
                ->all();
        } catch (QueryException $exception) {
            return response()->json([
                'message' => 'Connexion base indisponible ou non configuree.',
                'error' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'connection' => $validated['connection'],
            'tables' => $tables,
        ]);
    }

    public function schemaColumns(Request $request)
    {
        $this->enforcePermission('reservations', 'list', 'view');

        $allowedConnections = $this->allowedConnections();
        $validated = $request->validate([
            'connection' => ['required', Rule::in($allowedConnections)],
            'table' => ['required', 'string', 'max:150'],
        ]);

        try {
            $columns = collect(Schema::connection($validated['connection'])->getColumnListing($validated['table']))
                ->map(fn ($value) => (string) $value)
                ->values()
                ->all();
        } catch (QueryException $exception) {
            return response()->json([
                'message' => 'Table introuvable ou connexion indisponible.',
                'error' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'connection' => $validated['connection'],
            'table' => $validated['table'],
            'columns' => $columns,
        ]);
    }
}
