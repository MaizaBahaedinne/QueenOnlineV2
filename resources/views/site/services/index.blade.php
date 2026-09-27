@extends('layouts.public')

@section('content')
    <section class="page-hero">
        <div class="container hero-stage">
            <article class="hero-panel">
                <span class="eyebrow">Services</span>
                <h1 class="hero-title">Chaque service doit pouvoir se vendre seul.</h1>
                <p class="lead">Au lieu d une liste faible, la vitrine presente maintenant des portes d entree claires: categorie, niveau de prix, volume de ressources et acces direct au devis.</p>
                <div class="hero-meta-strip">
                    <div class="hero-meta-card">
                        <strong>{{ count($servicePages) }}</strong>
                        <span>univers consultables</span>
                    </div>
                    <div class="hero-meta-card">
                        <strong>{{ collect($servicePages)->sum(fn ($page) => $page['stats']['items']) }}</strong>
                        <span>ressources visibles</span>
                    </div>
                    <div class="hero-meta-card">
                        <strong>{{ collect($servicePages)->sum(fn ($page) => $page['stats']['packs']) }}</strong>
                        <span>packs listables</span>
                    </div>
                </div>
            </article>
            <aside class="hero-aside">
                <article class="hero-showcase">
                    <span class="eyebrow" style="background:rgba(255,255,255,0.14); color:#fff;">Lecture rapide</span>
                    <h3 style="margin-top:14px;">Le visiteur voit tout de suite</h3>
                    <ul class="showcase-list">
                        <li><span>Le type de service</span><strong>clair</strong></li>
                        <li><span>Le niveau d offre</span><strong>visible</strong></li>
                        <li><span>L action suivante</span><strong>devis</strong></li>
                    </ul>
                </article>
            </aside>
        </div>
    </section>

    <section class="section">
        <div class="container section-surface">
            <div class="card-grid">
                @foreach ($servicePages as $servicePage)
                    <article class="service-card">
                        <div class="service-card-top">
                            <div>
                                <span class="chip">{{ $servicePage['accent'] }}</span>
                                <h3 style="margin-top:14px;">{{ $servicePage['name'] }}</h3>
                            </div>
                            @if ($servicePage['startingPrice'])
                                <span class="price-badge">{{ $servicePage['startingPrice'] }}</span>
                            @endif
                        </div>
                        <p>{{ $servicePage['headline'] }}</p>
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
                        <div class="stack-actions">
                            <a href="{{ route('site.services.show', $servicePage['slug']) }}" class="btn btn-secondary">Ouvrir la page</a>
                            <a href="{{ route('site.quote', ['service' => $servicePage['slug']]) }}" class="btn btn-primary">Devis</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endsection