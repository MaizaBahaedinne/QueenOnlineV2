@extends('layouts.public')

@section('content')
    <section class="page-hero">
        <div class="hero-band">
            <div class="hero-slides" aria-hidden="true">
                <div class="hero-slide hero-slide-1"></div>
                <div class="hero-slide hero-slide-2"></div>
                <div class="hero-slide hero-slide-3"></div>
            </div>
            <div class="hero-overlay" aria-hidden="true"></div>
            <div class="container hero-stage">
                <article class="hero-panel">
                    <span class="eyebrow">Queen Park</span>
                    <h1 class="hero-title">Queen Park Tunisie, l adresse de vos plus beaux evenements.</h1>
                    <p class="lead">Mariage, fiancailles, reception privee ou celebration familiale: decouvrez un lieu, des services et un accompagnement pensés pour faire de chaque occasion un moment d exception.</p>
                    <div class="hero-meta-strip">
                        <div class="hero-meta-card">
                            <strong>{{ $siteStats['services'] }}</strong>
                            <span>univers de prestations a decouvrir</span>
                        </div>
                        <div class="hero-meta-card">
                            <strong>{{ $siteStats['active_items'] }}</strong>
                            <span>options et prestations disponibles</span>
                        </div>
                        <div class="hero-meta-card">
                            <strong>{{ $siteStats['active_rooms'] }}</strong>
                            <span>salles proposees selon vos besoins</span>
                        </div>
                    </div>
                    <div class="hero-actions">
                        <a href="{{ route('site.quote') }}" class="btn btn-primary">Obtenir un devis</a>
                        <a href="{{ route('site.services.index') }}" class="btn btn-secondary">Explorer les services</a>
                    </div>
                    <div class="quote-band">
                        <div>
                            <strong>Organisons ensemble votre evenement</strong>
                            <p>Notre equipe vous accompagne du premier contact jusqu a la selection de la formule la plus adaptee a votre celebration.</p>
                        </div>
                        <a href="{{ route('login') }}" class="btn btn-secondary">Se connecter</a>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-surface">
                <div class="section-head">
                    <div>
                        <span class="eyebrow">Services</span>
                        <h2>Des prestations pensees pour chaque moment fort</h2>
                        <p>Explorez nos univers de reception, d animation et d accompagnement pour composer un evenement a votre image.</p>
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
                                <span class="metric">{{ $servicePage['stats']['items'] }} option(s)</span>
                                @if ($servicePage['stats']['packs'] > 0)
                                    <span class="metric">{{ $servicePage['stats']['packs'] }} formule(s)</span>
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
                <h2 style="margin-top:14px;">Un cadre elegant pour recevoir avec distinction</h2>
                <p style="margin-top:14px;">Queen Park Tunisie vous propose un univers raffine pour accueillir vos invites, valoriser chaque detail et vivre un evenement soigneusement organise.</p>
                <ul class="bullet-list" style="margin-top:18px;">
                    <li>Des salles adaptees a differents formats de reception.</li>
                    <li>Des services complementaires pour enrichir votre experience.</li>
                    <li>Un contact simple pour demander des informations ou un devis.</li>
                </ul>
                <div class="stack-actions">
                    <a href="{{ route('site.about') }}" class="btn btn-secondary">Decouvrir Queen Park</a>
                </div>
            </article>

            <article class="info-card">
                <span class="eyebrow">Votre parcours</span>
                <h3 style="margin-top:14px;">Une visite simple, claire et rassurante</h3>
                <div class="process-grid" style="grid-template-columns:1fr; margin-top:18px;">
                    <article class="process-step">
                        <span class="process-step-index">A</span>
                        <h3>Decouvrir</h3>
                        <p class="muted" style="margin-top:10px;">Chaque page service vous presente les formules et les options disponibles de maniere claire.</p>
                    </article>
                    <article class="process-step">
                        <span class="process-step-index">B</span>
                        <h3>Choisir</h3>
                        <p class="muted" style="margin-top:10px;">Parcourez les services qui correspondent a votre evenement et a vos attentes.</p>
                    </article>
                    <article class="process-step">
                        <span class="process-step-index">C</span>
                        <h3>Nous contacter</h3>
                        <p class="muted" style="margin-top:10px;">Demandez votre devis et echangez avec notre equipe en toute simplicite.</p>
                    </article>
                </div>
            </article>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Salles en avant</span>
                        <h2>Des espaces concus pour recevoir vos invites</h2>
                        <p>Une premiere selection de salles pour vous aider a imaginer l ambiance, la capacite et le style de votre reception.</p>
                </div>
            </div>
            @if (count($featuredRooms) > 0)
                <div class="room-grid">
                    @foreach ($featuredRooms as $room)
                        <div class="room-card">
                            <strong>{{ $room['name'] }}</strong>
                            <p>{{ $room['description'] ?: 'Une salle Queen Park prete a accueillir votre evenement.' }}</p>
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
                <div class="empty-state">Aucune salle active n est disponible pour le moment.</div>
            @endif
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-head">
                <div>
                        <h2>Un parcours pense pour vos futurs evenements</h2>
                        <p>Tout est concu pour vous aider a comprendre l offre, trouver l inspiration et prendre contact facilement avec Queen Park.</p>
                    <p>Le site doit faire gagner du temps des deux cotes: comprendre l offre, comparer vite, puis demander un devis sans se perdre.</p>
                </div>
            </div>
            <div class="process-grid">
                <article class="process-step">
                    <span class="process-step-index">1</span>
                        <h3>Imaginer votre reception</h3>
                        <p class="muted" style="margin-top:10px;">L accueil donne tout de suite le ton et vous aide a vous projeter.</p>
                </article>
                <article class="process-step">
                    <span class="process-step-index">2</span>
                        <h3>Explorer les prestations</h3>
                        <p class="muted" style="margin-top:10px;">Chaque page service vous presente les formules et les options disponibles de maniere claire.</p>
                </article>
                <article class="process-step">
                    <span class="process-step-index">3</span>
                        <h3>Demander votre devis</h3>
                        <p class="muted" style="margin-top:10px;">Vous laissez votre besoin et notre equipe revient vers vous rapidement.</p>
                </article>
            </div>
        </div>
    </section>
@endsection