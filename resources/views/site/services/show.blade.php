@extends('layouts.public')

@section('content')
    <section class="page-hero">
        <div class="hero-band">
            <div class="hero-slides" aria-hidden="true">
                @if (!empty($servicePage['coverImageUrl']))
                    <div class="hero-slide hero-slide-photo hero-slide-static" data-bg="{{ $servicePage['coverImageUrl'] }}"></div>
                @else
                    <div class="hero-slide hero-slide-1"></div>
                    <div class="hero-slide hero-slide-2"></div>
                    <div class="hero-slide hero-slide-3"></div>
                @endif
            </div>
            <div class="hero-overlay" aria-hidden="true"></div>
            <div class="container hero-stage">
                <article class="hero-panel">
                    <span class="eyebrow">{{ $servicePage['accent'] }}</span>
                    <h1 class="hero-title">{{ $servicePage['name'] }}</h1>
                    <p class="lead">{{ $servicePage['headline'] }}</p>
                    <div class="hero-meta-strip">
                        <div class="hero-meta-card">
                            <strong>{{ $servicePage['stats']['items'] }}</strong>
                            <span>option(s) disponibles</span>
                        </div>
                        <div class="hero-meta-card">
                            <strong>{{ $servicePage['stats']['packs'] }}</strong>
                            <span>formule(s) proposee(s)</span>
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
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container section-head">
            <div>
                <span class="eyebrow">Apercu</span>
                <h2>Ce qu il faut savoir sur ce service</h2>
                <p>{{ $servicePage['summary'] }}</p>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container section-surface">
            <div class="section-head">
                <div>
                    <span class="eyebrow">Repere rapide</span>
                    <h2>Ce que ce service apporte</h2>
                </div>
            </div>
            <div class="process-grid">
                @foreach ($servicePage['highlights'] as $highlight)
                    <article class="process-step">
                        <span class="process-step-index">{{ $loop->iteration }}</span>
                        <h3>{{ $highlight }}</h3>
                        <p class="muted" style="margin-top:10px;">Un apercu rapide pour vous aider a identifier la formule qui vous correspond.</p>
                    </article>
                @endforeach
            </div>
            @if (count($servicePage['contactPhones']) > 0)
                <div class="metric-row" style="margin-top:18px;">
                    @foreach ($servicePage['contactPhones'] as $phone)
                        <span class="metric">{{ $phone }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="section">
        <div class="container two-col">
            <article class="info-card">
                <h3>Prestations disponibles</h3>
                @if (count($servicePage['items']) > 0)
                    <div class="mini-grid" style="margin-top:18px; grid-template-columns: 1fr;">
                        @foreach ($servicePage['items'] as $item)
                            <div class="mini-card">
                                @if (!empty($item['imageUrl']))
                                    <img
                                        src="{{ $item['imageUrl'] }}"
                                        alt="{{ $item['name'] }}"
                                        style="width:100%; height:160px; border-radius:12px; object-fit:cover; margin-bottom:12px;"
                                    >
                                @endif
                                <strong>{{ $item['name'] }}</strong>
                                <p>{{ $item['notes'] ?: 'Une prestation disponible pour accompagner votre evenement.' }}</p>
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
                                <p>{{ $room['description'] ?: 'Une salle Queen Park prete a accueillir votre reception.' }}</p>
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
                    <div class="empty-state" style="margin-top:18px;">Aucune prestation n est disponible pour le moment sur cette page.</div>
                @endif
            </article>

            <article class="info-card">
                <h3>Formules</h3>
                @if (count($servicePage['packs']) > 0)
                    <div class="mini-grid" style="margin-top:18px; grid-template-columns: 1fr;">
                        @foreach ($servicePage['packs'] as $pack)
                            <div class="mini-card">
                                <strong>{{ $pack['name'] }}</strong>
                                <p>{{ $pack['description'] ?: 'Une formule disponible pour ce service.' }}</p>
                                @if ($pack['price'])
                                    <div class="metric-row" style="margin-top:12px;">
                                        <span class="metric">{{ $pack['price'] }}</span>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state" style="margin-top:18px;">Aucune formule n est proposee pour le moment sur ce service.</div>
                @endif

                @if (count($servicePage['contactPhones']) > 0)
                    <h3 style="margin-top:24px;">Contacts utiles</h3>
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
            <h2 style="margin-top:14px; color:#fff;">Demandez une proposition adaptee a votre evenement</h2>
            <p>Si ce service correspond a vos attentes, notre equipe peut vous orienter rapidement avec un devis ou une prise de contact directe.</p>
            <div class="hero-actions">
                <a href="{{ route('site.quote', ['service' => $servicePage['slug']]) }}" class="btn btn-secondary">Demander un devis</a>
                <a href="{{ route('site.contact') }}" class="btn btn-secondary">Contacter l equipe</a>
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