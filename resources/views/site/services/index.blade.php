@extends('layouts.public')

@section('content')
    <section class="page-hero">
        <div class="container hero-panel">
            <span class="eyebrow">Services</span>
            <h1>Une page par service, branchee sur vos donnees internes.</h1>
            <p class="lead">Salles, troupe musicale, photographe, chanteur, notaire, animation et voiture: chaque page se nourrit des ressources et packs deja actifs dans la plateforme.</p>
        </div>
    </section>

    <section class="section">
        <div class="container card-grid">
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
    </section>
@endsection