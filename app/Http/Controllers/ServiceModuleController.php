<?php

namespace App\Http\Controllers;

use App\Models\ServiceModuleItem;
use App\Models\ServiceModulePack;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class ServiceModuleController extends MatrixAwareController
{
    private const MODULES = [
        'troupe-musicale' => ['name' => 'Troupe musicale', 'packs' => true],
        'photographe' => ['name' => 'Photographe', 'packs' => true],
        'chanteur' => ['name' => 'Chanteur', 'packs' => false],
        'notaire' => ['name' => 'Notaire', 'packs' => false],
        'animation' => ['name' => 'Animation', 'packs' => false],
        'voiture' => ['name' => 'Voiture', 'packs' => false],
    ];

    public function show(string $module)
    {
        $meta = $this->moduleMeta($module);
        $this->enforcePermission($module, 'list', 'view');

        if (! Schema::hasTable('service_module_items') || ! Schema::hasTable('service_module_packs')) {
            return view('service-modules/not-ready', [
                'title' => $meta['name'],
                'moduleMeta' => $meta,
            ]);
        }

        $items = ServiceModuleItem::query()
            ->where('module_slug', $module)
            ->latest()
            ->get();

        $troupes = collect();
        $partnershipPricesBySinger = [];
        if ($module === 'chanteur') {
            $troupes = ServiceModuleItem::query()
                ->where('module_slug', 'troupe-musicale')
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name']);

            $partnershipPricesBySinger = \App\Models\SingerTroupePartnershipPrice::query()
                ->whereIn('singer_item_id', $items->pluck('id'))
                ->get(['singer_item_id', 'troupe_item_id', 'partnership_price'])
                ->groupBy('singer_item_id')
                ->map(function ($rows) {
                    return $rows->mapWithKeys(function ($row) {
                        return [(string) $row->troupe_item_id => (string) $row->partnership_price];
                    })->all();
                })
                ->all();
        }

        return view('service-modules.show', [
            'title' => $meta['name'],
            'moduleSlug' => $module,
            'moduleMeta' => $meta,
            'items' => $items,
            'troupes' => $troupes,
            'partnershipPricesBySinger' => $partnershipPricesBySinger,
        ]);
    }

    public function showPacks(string $module)
    {
        $meta = $this->moduleMeta($module);
        abort_unless($meta['packs'], 404);
        $this->enforcePermission($module, 'list-pack', 'view');

        if (! Schema::hasTable('service_module_items') || ! Schema::hasTable('service_module_packs')) {
            return view('service-modules/not-ready', [
                'title' => $meta['name'],
                'moduleMeta' => $meta,
            ]);
        }

        $items = ServiceModuleItem::query()
            ->where('module_slug', $module)
            ->latest()
            ->get();

        $packs = ServiceModulePack::query()
            ->with('item')
            ->where('module_slug', $module)
            ->latest()
            ->get();

        return view('service-modules.packs', [
            'title' => $meta['name'],
            'moduleSlug' => $module,
            'moduleMeta' => $meta,
            'items' => $items,
            'packs' => $packs,
        ]);
    }

    public function storeItem(Request $request, string $module)
    {
        $this->moduleMeta($module);
        $this->enforcePermission($module, 'create', 'create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);

        ServiceModuleItem::query()->create([
            'module_slug' => $module,
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'base_price' => $validated['base_price'] ?? 0,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('service-modules.show', $module)->with('success', 'Element ajoute.');
    }

    public function updateItem(Request $request, string $module, ServiceModuleItem $item)
    {
        $this->assertItemInModule($module, $item);
        $this->enforcePermission($module, 'update', 'update');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ]);

        $item->update($validated);

        return redirect()->route('service-modules.show', $module)->with('success', 'Element mis a jour.');
    }

    public function destroyItem(string $module, ServiceModuleItem $item)
    {
        $this->assertItemInModule($module, $item);
        $this->enforcePermission($module, 'delete', 'delete');

        $item->delete();

        return redirect()->route('service-modules.show', $module)->with('success', 'Element supprime.');
    }

    public function updatePartnershipPrices(Request $request, string $module, ServiceModuleItem $item)
    {
        $this->assertItemInModule($module, $item);
        $this->enforcePermission($module, 'update', 'update');
        abort_unless($module === 'chanteur', 404);

        $troupeIds = ServiceModuleItem::query()
            ->where('module_slug', 'troupe-musicale')
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'partnership_prices' => ['nullable', 'array'],
            'partnership_prices.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $prices = $validated['partnership_prices'] ?? [];

        DB::transaction(function () use ($item, $prices, $troupeIds) {
            \App\Models\SingerTroupePartnershipPrice::query()
                ->where('singer_item_id', $item->id)
                ->delete();

            foreach ($prices as $troupeId => $price) {
                if (! in_array((int) $troupeId, $troupeIds, true)) {
                    continue;
                }

                if ($price === null || $price === '') {
                    continue;
                }

                \App\Models\SingerTroupePartnershipPrice::query()->create([
                    'singer_item_id' => $item->id,
                    'troupe_item_id' => (int) $troupeId,
                    'partnership_price' => $price,
                ]);
            }
        });

        return redirect()->route('service-modules.show', $module)->with('success', 'Prix de partenariat mis a jour.');
    }

    public function storePack(Request $request, string $module)
    {
        $meta = $this->moduleMeta($module);
        abort_unless($meta['packs'], 404);
        $this->enforcePermission($module, 'create-pack', 'create');

        $validated = $request->validate([
            'service_module_item_id' => ['nullable', 'exists:service_module_items,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string'],
        ]);

        ServiceModulePack::query()->create([
            'module_slug' => $module,
            'service_module_item_id' => $validated['service_module_item_id'] ?? null,
            'name' => $validated['name'],
            'price' => $validated['price'] ?? 0,
            'status' => $validated['status'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()->route('service-modules.packs.index', $module)->with('success', 'Pack ajoute.');
    }

    public function updatePack(Request $request, string $module, ServiceModulePack $pack)
    {
        $this->assertPackInModule($module, $pack);
        $this->enforcePermission($module, 'update-pack', 'update');

        $validated = $request->validate([
            'service_module_item_id' => ['nullable', 'exists:service_module_items,id'],
            'name' => ['required', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'description' => ['nullable', 'string'],
        ]);

        $pack->update($validated);

        return redirect()->route('service-modules.packs.index', $module)->with('success', 'Pack mis a jour.');
    }

    public function destroyPack(string $module, ServiceModulePack $pack)
    {
        $this->assertPackInModule($module, $pack);
        $this->enforcePermission($module, 'delete-pack', 'delete');

        $pack->delete();

        return redirect()->route('service-modules.packs.index', $module)->with('success', 'Pack supprime.');
    }

    private function moduleMeta(string $module): array
    {
        abort_unless(array_key_exists($module, self::MODULES), 404);

        return self::MODULES[$module];
    }

    private function assertItemInModule(string $module, ServiceModuleItem $item): void
    {
        $this->moduleMeta($module);
        abort_if($item->module_slug !== $module, 404);
    }

    private function assertPackInModule(string $module, ServiceModulePack $pack): void
    {
        $this->moduleMeta($module);
        abort_if($pack->module_slug !== $module, 404);
    }
}
