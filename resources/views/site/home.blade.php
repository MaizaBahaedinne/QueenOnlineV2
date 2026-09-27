@extends('layouts.public')

@section('content')
    <section class="page-hero">
        <div class="container hero-stage">
            <article class="hero-panel">
                <span class="eyebrow">Queen Park</span>
                <h1 class="hero-title">Des evenements mieux presentes, mieux orientes, mieux convertis.</h1>
                <p class="lead">Queen Park expose les vraies ressources deja actives dans la plateforme, mais avec une interface publique plus lisible pour rassurer, expliquer l offre et pousser vers le devis.</p>
                <div class="hero-meta-strip">
                    <div class="hero-meta-card">
                        <strong>{{ $siteStats['services'] }}</strong>
                        <span>services publics relies a la base interne</span>
                    </div>
                    <div class="hero-meta-card">
                        <strong>{{ $siteStats['active_items'] }}</strong>
                        <span>ressources actives visibles sans ressaisie</span>
                    </div>
                    <div class="hero-meta-card">
                        <strong>{{ $siteStats['active_rooms'] }}</strong>
                        <span>salles affichables avec capacite et prix</span>
                    </div>
                </div>
                <div class="hero-actions">
                    <a href="{{ route('site.quote') }}" class="btn btn-primary">Obtenir un devis</a>
                    <a href="{{ route('site.services.index') }}" class="btn btn-secondary">Explorer les services</a>
                </div>
                <div class="quote-band">
                    <div>
                        <strong>Une vitrine enfin exploitable commercialement</strong>
                        <p>Presentation claire, CTA visibles, pages service distinctes et demandes de devis centralisees dans le meme projet.</p>
                    </div>
                    <a href="{{ route('login') }}" class="btn btn-secondary">Acces equipe</a>
                </div>
            </article>

            <aside class="hero-aside">
                <article class="hero-showcase">
                    <span class="eyebrow" style="background:rgba(255,255,255,0.14); color:#fff;">Vision rapide</span>
                    <h3 style="margin-top:14px;">Parcours visiteur plus net</h3>
                    <div class="hero-showcase-grid">
                        <div class="hero-showcase-card">
                            <strong>Accueil</strong>
                            <p>Comprendre l offre en quelques secondes.</p>
                        </div>
                        <div class="hero-showcase-card">
                            <strong>Services</strong>
                            <p>Entrer par besoin plutot que par structure interne.</p>
                        </div>
                        <div class="hero-showcase-card">
                            <strong>Devis</strong>
                            <p>Transformer l interet en demande qualifiee.</p>
                        </div>
                    </div>
                    @if (count($featuredRooms) > 0)
                        @php $heroRoom = $featuredRooms[0]; @endphp
                        <div class="hero-room-card" style="margin-top:16px;">
                            <strong>{{ $heroRoom['name'] }}</strong>
                            <p>{{ $heroRoom['description'] ?: 'Salle active issue de la plateforme.' }}</p>
                            <div class="metric-row" style="margin-top:12px;">
                                <span class="metric" style="background:rgba(255,255,255,0.14); color:#fff; border-color:rgba(255,255,255,0.18);">{{ $heroRoom['capacity'] }} invites</span>
                                @if ($heroRoom['price'])
                                    <span class="metric" style="background:rgba(255,255,255,0.14); color:#fff; border-color:rgba(255,255,255,0.18);">{{ $heroRoom['price'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </article>
                <article class="hero-secondary-card">
                    <span class="eyebrow">Donnees directes</span>
                    <h3 style="margin-top:14px;">Ce que le site sait deja montrer</h3>
                    <ul class="showcase-list">
                        <li><span>Prestataires actifs</span><strong>{{ $siteStats['active_items'] }}</strong></li>
                        <li><span>Packs prets a vendre</span><strong>{{ $siteStats['active_packs'] }}</strong></li>
                        <li><span>Services consultables</span><strong>{{ $siteStats['services'] }}</strong></li>
                    </ul>
                </article>
            </aside>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-surface">
                <div class="section-head">
                    <div>
                        <span class="eyebrow">Services</span>
                        <h2>Des pages service reliees a l operationnel</h2>
                        <p>Chaque service expose un apercu exploitable: volumes actifs, prix d entree, packs disponibles et ressources deja renseignees dans votre systeme.</p>
                    </div>
                    <a href="{{ route('site.services.index') }}" class="btn btn-secondary">Voir tout</a>
                </div>

                <div class="card-grid">
                    @foreach (array_slice($servicePages, 0, 6) as $servicePage)
                        <article class="service-card">
                            <div class="service-card-top">
                                <div>
                                    <span class="chip">{{ $servicePage['accent'] }}</span>
                                    <h3 style="margin-top:14px;">{{ $servicePage['name'] }}</h3>
                                </div>
                                @if ($servicePage['startingPrice'])
                                    <span class="price-badge">Des {{ $servicePage['startingPrice'] }}</span>
                                @endif
                            </div>
                            <p>{{ $servicePage['summary'] }}</p>
                            <div class="metric-row">
                                <span class="metric">{{ $servicePage['stats']['items'] }} ressource(s)</span>
                                @if ($servicePage['stats']['packs'] > 0)
                                    <span class="metric">{{ $servicePage['stats']['packs'] }} pack(s)</span>
                                @endif
                            </div>
                            <ul class="bullet-list">
                                @foreach ($servicePage['highlights'] as $highlight)
                                    <li>{{ $highlight }}</li>
                                @endforeach
                            </ul>
                            <a href="{{ route('site.services.show', $servicePage['slug']) }}" class="btn btn-secondary">Voir la page service</a>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container two-col">
            <article class="info-card">
                <span class="eyebrow">A propos</span>
                <h2 style="margin-top:14px;">Une organisation evenementielle centralisee</h2>
                <p style="margin-top:14px;">Queen Park ne presente pas un catalogue figé. La vitrine s appuie sur les memes donnees que vos equipes utilisent pour exploiter les reservations, les prestataires et les services additionnels.</p>
                <ul class="bullet-list" style="margin-top:18px;">
                    <li>Les salles viennent du module de gestion des espaces.</li>
                    <li>Les pages services lisent les items et packs actifs de la plateforme.</li>
                    <li>Les formulaires contact et devis creent des demandes tracables en base.</li>
                </ul>
                <div class="stack-actions">
                    <a href="{{ route('site.about') }}" class="btn btn-secondary">Lire l histoire</a>
                </div>
            </article>

            <article class="info-card">
                <span class="eyebrow">Salles en avant</span>
                <h3 style="margin-top:14px;">Capacites, lieux et positionnement tarifaire</h3>
                @if (count($featuredRooms) > 0)
                    <div class="room-grid" style="margin-top:18px; grid-template-columns: 1fr;">
                        @foreach ($featuredRooms as $room)
                            <div class="room-card">
                                <strong>{{ $room['name'] }}</strong>
                                <p>{{ $room['description'] ?: 'Salle active disponible dans la plateforme.' }}</p>
                                <div class="metric-row" style="margin-top:12px;">
                                    <span class="metric">{{ $room['capacity'] }} invites</span>
                                    @if ($room['price'])
                                        <span class="metric">{{ $room['price'] }}</span>
                                    @endif
                                    @if ($room['location'])
                                        <span class="metric">{{ $room['location'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state" style="margin-top:18px;">Aucune salle active n est disponible pour le moment.</div>
                @endif
            </article>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Parcours</span>
                    <h2>Une UX plus simple pour le visiteur</h2>
                    <p>Le site doit faire gagner du temps des deux cotes: comprendre l offre, comparer vite, puis demander un devis sans se perdre.</p>
                </div>
            </div>
            <div class="process-grid">
                <article class="process-step">
                    <span class="process-step-index">1</span>
                    <h3>Entrer par le besoin</h3>
                    <p class="muted" style="margin-top:10px;">Le hero explique en une phrase ce que Queen Park vend et comment avancer.</p>
                </article>
                <article class="process-step">
                    <span class="process-step-index">2</span>
                    <h3>Parcourir les services</h3>
                    <p class="muted" style="margin-top:10px;">Chaque service dispose d une page distincte avec ressources, packs et points d entree.</p>
                </article>
                <article class="process-step">
                    <span class="process-step-index">3</span>
                    <h3>Transformer en demande</h3>
                    <p class="muted" style="margin-top:10px;">Le visiteur bascule vers Contact ou Devis avec un formulaire plus direct et mieux contextualise.</p>
                </article>
            </div>
        </div>
    </section>
@endsection