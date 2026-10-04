@extends('layouts.public')

@section('content')
    @php
        $heroPhotos = collect($servicePages)
            ->pluck('coverImageUrl')
            ->filter()
            ->take(3)
            ->values();
    @endphp

    <section class="page-hero">
        <div class="hero-band">
            <div class="hero-slides" aria-hidden="true">
                @if ($heroPhotos->count() > 0)
                    @foreach ($heroPhotos as $index => $photoUrl)
                        <div
                            class="hero-slide hero-slide-photo hero-slide-{{ ($index % 3) + 1 }}"
                            data-bg="{{ $photoUrl }}"
                        ></div>
                    @endforeach
                @else
                    <div class="hero-slide hero-slide-1"></div>
                    <div class="hero-slide hero-slide-2"></div>
                    <div class="hero-slide hero-slide-3"></div>
                @endif
            </div>
            <div class="hero-overlay" aria-hidden="true"></div>
            <div class="container hero-stage">
                <article class="hero-panel">
                    <span class="eyebrow">Services</span>
                    <h1 class="hero-title">Des services qui donnent du style a chaque evenement.</h1>
                    <p class="lead">Explorez les prestations Queen Park pour composer une reception harmonieuse, elegante et adaptee a votre occasion.</p>
                    <div class="hero-meta-strip">
                        <div class="hero-meta-card">
                            <strong>{{ count($servicePages) }}</strong>
                            <span>univers de prestations</span>
                        </div>
                        <div class="hero-meta-card">
                            <strong>{{ collect($servicePages)->sum(fn ($page) => $page['stats']['items']) }}</strong>
                            <span>options disponibles</span>
                        </div>
                        <div class="hero-meta-card">
                            <strong>{{ collect($servicePages)->sum(fn ($page) => $page['stats']['packs']) }}</strong>
                            <span>formules a explorer</span>
                        </div>
                    </div>
                </article>
            </div>
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
                        <div class="stack-actions">
                            <a href="{{ route('site.services.show', $servicePage['slug']) }}" class="btn btn-secondary">Ouvrir la page</a>
                            <a href="{{ route('site.quote', ['service' => $servicePage['slug']]) }}" class="btn btn-primary">Devis</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <script>
        document.querySelectorAll('.hero-slide-photo[data-bg]').forEach((slide) => {
            const imageUrl = slide.getAttribute('data-bg');
            if (imageUrl) {
                slide.style.backgroundImage = `url("${imageUrl}")`;
            }
        });
    </script>
@endsection