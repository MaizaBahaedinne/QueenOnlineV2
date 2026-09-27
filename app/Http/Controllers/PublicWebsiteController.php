<?php

namespace App\Http\Controllers;

use App\Models\PublicInquiry;
use App\Models\Salle;
use App\Models\ServiceModuleItem;
use App\Models\ServiceModulePack;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicWebsiteController extends Controller
{
    private const SERVICE_META = [
        'salles' => [
            'name' => 'Salles de fete',
            'headline' => 'Des espaces pour mariages, fiancailles et evenements prives.',
            'summary' => 'Capacites, tarifs et disponibilites de nos salles, directement depuis la plateforme.',
            'accent' => 'Reception',
        ],
        'troupe-musicale' => [
            'name' => 'Troupe musicale',
            'headline' => 'Des formations pour accompagner chaque temps fort.',
            'summary' => 'Selection de troupes, formules et budgets issus de la gestion interne.',
            'accent' => 'Ambiance live',
        ],
        'photographe' => [
            'name' => 'Photographe',
            'headline' => 'Couverture photo pour immortaliser chaque moment.',
            'summary' => 'Prestataires, packs et fourchettes tarifaires mis a jour depuis la plateforme.',
            'accent' => 'Image',
        ],
        'chanteur' => [
            'name' => 'Chanteur',
            'headline' => 'Voix, scene et presence pour vos soirees et ceremonies.',
            'summary' => 'Artistes et tarifs de base relies a la gestion de service.',
            'accent' => 'Performance',
        ],
        'notaire' => [
            'name' => 'Notaire',
            'headline' => 'Un accompagnement administratif dans votre parcours evenementiel.',
            'summary' => 'Intervenants disponibles et organisation simplifiee pour les formalites.',
            'accent' => 'Formalites',
        ],
        'animation' => [
            'name' => 'Animation',
            'headline' => 'Des interventions pour rythmer et energiser l evenement.',
            'summary' => 'Options d animation gerees dans la meme base que vos services operationnels.',
            'accent' => 'Experience',
        ],
        'voiture' => [
            'name' => 'Voiture',
            'headline' => 'Vehicules et trajets pour vos arrivees, departs et deplacements.',
            'summary' => 'Ressources transport et niveaux de prix issus de la plateforme de reservation.',
            'accent' => 'Transport',
        ],
    ];

    public function home(): View
    {
        $site = $this->buildSitePayload();

        return view('site.home', $site + [
            'title' => 'Queen Park',
        ]);
    }

    public function about(): View
    {
        $site = $this->buildSitePayload();

        return view('site.about', $site + [
            'title' => 'A propos',
        ]);
    }

    public function services(): View
    {
        $site = $this->buildSitePayload();

        return view('site.services.index', $site + [
            'title' => 'Services',
        ]);
    }

    public function service(string $service): View
    {
        $site = $this->buildSitePayload();
        $servicePage = collect($site['servicePages'])->firstWhere('slug', $service);

        abort_if($servicePage === null, 404);

        return view('site.services.show', $site + [
            'title' => $servicePage['name'],
            'servicePage' => $servicePage,
        ]);
    }

    public function contact(): View
    {
        $site = $this->buildSitePayload();

        return view('site.contact', $site + [
            'title' => 'Contact',
            'selectedServiceSlug' => old('service_slug'),
        ]);
    }

    public function quote(Request $request): View
    {
        $site = $this->buildSitePayload();
        $selectedServiceSlug = trim((string) $request->query('service', old('service_slug', '')));

        return view('site.quote', $site + [
            'title' => 'Obtenir un devis',
            'selectedServiceSlug' => array_key_exists($selectedServiceSlug, self::SERVICE_META) ? $selectedServiceSlug : '',
        ]);
    }

    public function storeInquiry(Request $request): RedirectResponse
    {
        if (! Schema::hasTable('public_inquiries')) {
            return back()
                ->withInput()
                ->withErrors(['site' => 'Le module de demandes publiques n est pas encore initialise. Lance la migration avant de recevoir des demandes.']);
        }

        $allowedServiceSlugs = array_keys(self::SERVICE_META);
        $validated = $request->validate([
            'request_type' => ['required', Rule::in(['contact', 'quote'])],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'service_slug' => ['nullable', Rule::in($allowedServiceSlugs), 'required_if:request_type,quote'],
            'event_date' => ['nullable', 'date', 'after_or_equal:today'],
            'guest_count' => ['nullable', 'integer', 'min:1'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'message' => ['required', 'string', 'max:4000'],
            'source_page' => ['nullable', 'string', 'max:150'],
        ]);

        PublicInquiry::query()->create([
            'request_type' => $validated['request_type'],
            'full_name' => trim((string) $validated['full_name']),
            'phone' => trim((string) $validated['phone']),
            'email' => isset($validated['email']) ? trim((string) $validated['email']) : null,
            'service_slug' => $validated['service_slug'] ?? null,
            'event_date' => $validated['event_date'] ?? null,
            'guest_count' => $validated['guest_count'] ?? null,
            'budget' => $validated['budget'] ?? null,
            'message' => trim((string) $validated['message']),
            'source_page' => trim((string) ($validated['source_page'] ?? 'site')),
            'status' => 'new',
        ]);

        $routeName = ($validated['request_type'] ?? 'contact') === 'quote' ? 'site.quote' : 'site.contact';

        return redirect()->route($routeName)->with('success', 'Votre demande a ete envoyee. L equipe Queen Park vous recontactera rapidement.');
    }

    private function buildSitePayload(): array
    {
        $servicePages = $this->buildServicePages();
        $featuredRooms = collect($servicePages)
            ->firstWhere('slug', 'salles')['rooms'] ?? [];

        $serviceCount = count($servicePages);
        $activeItems = collect($servicePages)->sum(function (array $servicePage): int {
            return (int) ($servicePage['stats']['items'] ?? 0);
        });
        $activePacks = collect($servicePages)->sum(function (array $servicePage): int {
            return (int) ($servicePage['stats']['packs'] ?? 0);
        });
        $phoneNumbers = collect($servicePages)
            ->flatMap(function (array $servicePage): array {
                return $servicePage['contactPhones'] ?? [];
            })
            ->filter()
            ->unique()
            ->take(4)
            ->values()
            ->all();

        return [
            'servicePages' => $servicePages,
            'featuredRooms' => array_slice($featuredRooms, 0, 3),
            'siteStats' => [
                'services' => $serviceCount,
                'active_items' => $activeItems,
                'active_packs' => $activePacks,
                'active_rooms' => count($featuredRooms),
            ],
            'contactPhones' => $phoneNumbers,
            'serviceOptions' => collect($servicePages)->map(fn (array $page) => [
                'slug' => $page['slug'],
                'name' => $page['name'],
            ])->all(),
        ];
    }

    private function buildServicePages(): array
    {
        $itemsByModule = $this->activeItemsByModule();
        $packsByModule = $this->activePacksByModule();
        $rooms = $this->activeRooms();

        $pages = [];
        foreach (self::SERVICE_META as $slug => $meta) {
            if ($slug === 'salles') {
                $pages[] = $this->buildSallePage($slug, $meta, $rooms);
                continue;
            }

            $items = $itemsByModule->get($slug, collect());
            $packs = $packsByModule->get($slug, collect());
            $lowestItemPrice = $items->min('base_price');
            $lowestPackPrice = $packs->min('price');
            $startingPrice = $lowestPackPrice !== null ? $lowestPackPrice : $lowestItemPrice;

            $pages[] = [
                'slug' => $slug,
                'name' => $meta['name'],
                'accent' => $meta['accent'],
                'headline' => $meta['headline'],
                'summary' => $meta['summary'],
                'startingPrice' => $this->formatPrice($startingPrice),
                'stats' => [
                    'items' => $items->count(),
                    'packs' => $packs->count(),
                ],
                'highlights' => $this->buildHighlights($slug, $items, $packs),
                'items' => $items->take(6)->map(function (ServiceModuleItem $item): array {
                    return [
                        'name' => $item->name,
                        'price' => $this->formatPrice($item->base_price),
                        'notes' => $item->notes,
                    ];
                })->all(),
                'packs' => $packs->take(6)->map(function (ServiceModulePack $pack): array {
                    return [
                        'name' => $pack->name,
                        'price' => $this->formatPrice($pack->price),
                        'description' => $pack->description,
                    ];
                })->all(),
                'rooms' => [],
                'contactPhones' => $items->pluck('phone')->filter()->unique()->take(3)->values()->all(),
            ];
        }

        return $pages;
    }

    private function buildSallePage(string $slug, array $meta, Collection $rooms): array
    {
        return [
            'slug' => $slug,
            'name' => $meta['name'],
            'accent' => $meta['accent'],
            'headline' => $meta['headline'],
            'summary' => $meta['summary'],
            'startingPrice' => $this->formatPrice($rooms->min('price_per_day')),
            'stats' => [
                'items' => $rooms->count(),
                'packs' => 0,
            ],
            'highlights' => [
                $rooms->count() . ' salle(s) active(s)',
                'Capacites jusqu a ' . ($rooms->max('capacity') ?: 0) . ' invites',
                'Tarifs relies a la gestion interne',
            ],
            'items' => [],
            'packs' => [],
            'rooms' => $rooms->take(6)->map(function (Salle $room): array {
                return [
                    'name' => $room->name,
                    'capacity' => (int) $room->capacity,
                    'price' => $this->formatPrice($room->price_per_day),
                    'location' => $room->location,
                    'description' => $room->description,
                ];
            })->all(),
            'contactPhones' => [],
        ];
    }

    private function activeItemsByModule(): Collection
    {
        if (! Schema::hasTable('service_module_items')) {
            return collect();
        }

        return ServiceModuleItem::query()
            ->where('status', 'active')
            ->orderBy('module_slug')
            ->orderBy('name')
            ->get()
            ->groupBy('module_slug');
    }

    private function activePacksByModule(): Collection
    {
        if (! Schema::hasTable('service_module_packs')) {
            return collect();
        }

        return ServiceModulePack::query()
            ->where('status', 'active')
            ->orderBy('module_slug')
            ->orderBy('price')
            ->get()
            ->groupBy('module_slug');
    }

    private function activeRooms(): Collection
    {
        if (! Schema::hasTable('salles')) {
            return collect();
        }

        return Salle::query()
            ->where('status', 'active')
            ->orderByDesc('capacity')
            ->orderBy('price_per_day')
            ->get();
    }

    private function buildHighlights(string $slug, Collection $items, Collection $packs): array
    {
        $highlights = [
            $items->count() . ' prestataire(s) actif(s)',
        ];

        if ($packs->isNotEmpty()) {
            $highlights[] = $packs->count() . ' pack(s) disponible(s)';
        }

        $phoneCount = $items->pluck('phone')->filter()->count();
        if ($phoneCount > 0) {
            $highlights[] = $phoneCount . ' contact(s) operationnel(s)';
        }

        if ($slug === 'voiture') {
            $highlights[] = 'Trajets et ressources relies au module de reservation';
        }

        return array_slice($highlights, 0, 3);
    }

    private function formatPrice(null|int|float|string $value): ?string
    {
        if ($value === null || $value === '' || (float) $value <= 0) {
            return null;
        }

        return number_format((float) $value, 0, ',', ' ') . ' TND';
    }
}