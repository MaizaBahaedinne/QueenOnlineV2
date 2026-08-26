<?php

namespace App\Http\Controllers;

use App\Models\MigrationMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class MigrationMappingController extends MatrixAwareController
{
    public function index(Request $request)
    {
        $this->enforcePermission('modules', 'list', 'view');

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

        return view('migration-mappings.index', [
            'title' => 'Mapping migration',
            'mappings' => $rows,
            'sourceTableFilter' => $sourceTableFilter,
            'sourceTables' => MigrationMapping::query()->select('source_table')->distinct()->orderBy('source_table')->pluck('source_table'),
        ]);
    }

    public function store(Request $request)
    {
        $this->enforcePermission('modules', 'create', 'create');

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
        $this->enforcePermission('modules', 'update', 'update');

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
        $this->enforcePermission('modules', 'delete', 'delete');

        $migrationMapping->delete();

        return redirect()->route('migration-mappings.index')->with('success', 'Ligne de mapping supprimee.');
    }

    public function export(Request $request)
    {
        $this->enforcePermission('modules', 'list', 'view');

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
}
