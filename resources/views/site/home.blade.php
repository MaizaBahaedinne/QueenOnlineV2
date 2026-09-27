@extends('layouts.public')

@section('content')
    <section class="page-hero">
        <div class="container hero-grid">
            <article class="hero-panel">
                <span class="eyebrow">Queen Park</span>
                <h1>Une vitrine reliee a vos vraies donnees services.</h1>
                <p class="lead">Salles, photo, animation, musique, transport et demandes de devis: le site public s alimente a partir de la plateforme que vous utilisez deja pour gerer vos ressources.</p>
                <div class="hero-actions">
                    <a href="{{ route('site.quote') }}" class="btn btn-primary">Obtenir un devis</a>
                    <a href="{{ route('site.services.index') }}" class="btn btn-secondary">Explorer les services</a>
                </div>
                <div class="quote-band">
                    <div>
                        <strong>Un seul socle de donnees</strong>
                        <p>Les services visibles ici reprennent les items, packs et salles deja actives dans la plateforme Queen Park.</p>
                    </div>
                    <a href="{{ route('login') }}" class="btn btn-secondary">Acces equipe</a>
                </div>
            </article>

            <div class="stats-grid">
                <article class="stat-card">
                    <span>Services visibles</span>
                    <strong>{{ $siteStats['services'] }}</strong>
                </article>
                <article class="stat-card">
                    <span>Prestataires actifs</span>
                    <strong>{{ $siteStats['active_items'] }}</strong>
                </article>
                <article class="stat-card">
                    <span>Packs actifs</span>
                    <strong>{{ $siteStats['active_packs'] }}</strong>
                </article>
                <article class="stat-card">
                    <span>Salles actives</span>
                    <strong>{{ $siteStats['active_rooms'] }}</strong>
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
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
@endsection