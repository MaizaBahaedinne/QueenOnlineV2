@extends('layouts.public')

@section('content')
    <section class="page-hero">
        <div class="container hero-stage">
            <article class="hero-panel">
                <span class="eyebrow">{{ $servicePage['accent'] }}</span>
                <h1 class="hero-title">{{ $servicePage['name'] }}</h1>
                <p class="lead">{{ $servicePage['headline'] }}</p>
                <div class="hero-meta-strip">
                    <div class="hero-meta-card">
                        <strong>{{ $servicePage['stats']['items'] }}</strong>
                        <span>ressource(s) actives</span>
                    </div>
                    <div class="hero-meta-card">
                        <strong>{{ $servicePage['stats']['packs'] }}</strong>
                        <span>pack(s) associe(s)</span>
                    </div>
                    <div class="hero-meta-card">
                        <strong>{{ $servicePage['startingPrice'] ?: 'Sur demande' }}</strong>
                        <span>niveau de depart</span>
                    </div>
                </div>
                <div class="hero-actions">
                    <a href="{{ route('site.quote', ['service' => $servicePage['slug']]) }}" class="btn btn-primary">Obtenir un devis</a>
                    <a href="{{ route('site.contact') }}" class="btn btn-secondary">Parler a l equipe</a>
                </div>
            </article>

            <aside class="hero-aside">
                <article class="hero-showcase">
                    <span class="eyebrow" style="background:rgba(255,255,255,0.14); color:#fff;">Repere rapide</span>
                    <h3 style="margin-top:14px;">Ce que ce service apporte</h3>
                    <ul class="showcase-list">
                        @foreach ($servicePage['highlights'] as $highlight)
                            <li><span>{{ $highlight }}</span><strong></strong></li>
                        @endforeach
                    </ul>
                </article>
                @if (count($servicePage['contactPhones']) > 0)
                    <article class="hero-secondary-card">
                        <span class="eyebrow">Contacts</span>
                        <h3 style="margin-top:14px;">Reperes operationnels</h3>
                        <div class="metric-row" style="margin-top:14px;">
                            @foreach ($servicePage['contactPhones'] as $phone)
                                <span class="metric">{{ $phone }}</span>
                            @endforeach
                        </div>
                    </article>
                @endif
            </aside>
        </div>
    </section>

    <section class="section">
        <div class="container section-head">
            <div>
                <span class="eyebrow">Apercu</span>
                <h2>Ce que le module expose actuellement</h2>
                <p>{{ $servicePage['summary'] }}</p>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container two-col">
            <article class="info-card">
                <h3>Ressources actives</h3>
                @if (count($servicePage['items']) > 0)
                    <div class="mini-grid" style="margin-top:18px; grid-template-columns: 1fr;">
                        @foreach ($servicePage['items'] as $item)
                            <div class="mini-card">
                                <strong>{{ $item['name'] }}</strong>
                                <p>{{ $item['notes'] ?: 'Ressource active disponible dans la plateforme.' }}</p>
                                <div class="metric-row" style="margin-top:12px;">
                                    @if ($item['price'])
                                        <span class="metric">{{ $item['price'] }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @elseif (count($servicePage['rooms']) > 0)
                    <div class="room-grid" style="margin-top:18px; grid-template-columns: 1fr;">
                        @foreach ($servicePage['rooms'] as $room)
                            <div class="room-card">
                                <strong>{{ $room['name'] }}</strong>
                                <p>{{ $room['description'] ?: 'Salle active disponible pour la vitrine.' }}</p>
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
                    <div class="empty-state" style="margin-top:18px;">Aucune ressource active n est visible pour ce service.</div>
                @endif
            </article>

            <article class="info-card">
                <h3>Packs et formule</h3>
                @if (count($servicePage['packs']) > 0)
                    <div class="mini-grid" style="margin-top:18px; grid-template-columns: 1fr;">
                        @foreach ($servicePage['packs'] as $pack)
                            <div class="mini-card">
                                <strong>{{ $pack['name'] }}</strong>
                                <p>{{ $pack['description'] ?: 'Pack actif disponible pour ce service.' }}</p>
                                @if ($pack['price'])
                                    <div class="metric-row" style="margin-top:12px;">
                                        <span class="metric">{{ $pack['price'] }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state" style="margin-top:18px;">Aucun pack actif n est encore rattache a ce service.</div>
                @endif

                @if (count($servicePage['contactPhones']) > 0)
                    <h3 style="margin-top:24px;">Contacts reperes</h3>
                    <div class="metric-row" style="margin-top:14px;">
                        @foreach ($servicePage['contactPhones'] as $phone)
                            <span class="metric">{{ $phone }}</span>
                        @endforeach
                    </div>
                @endif
            </article>
        </div>
    </section>

    <section class="section">
        <div class="container cta-panel">
            <span class="eyebrow" style="background:rgba(255,255,255,0.14); color:#fff;">Action</span>
            <h2 style="margin-top:14px; color:#fff;">Passer du parcours de lecture au besoin concret</h2>
            <p>Si ce service correspond au besoin du client, la suite logique doit etre immediate: demande de devis ou prise de contact, sans aller-retour inutile.</p>
            <div class="hero-actions">
                <a href="{{ route('site.quote', ['service' => $servicePage['slug']]) }}" class="btn btn-secondary">Demander un devis</a>
                <a href="{{ route('site.contact') }}" class="btn btn-secondary">Contacter l equipe</a>
            </div>
        </div>
    </section>
@endsection