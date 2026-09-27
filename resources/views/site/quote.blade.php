@extends('layouts.public')

@section('content')
    <section class="page-hero">
        <div class="container hero-stage">
            <article class="hero-panel">
                <span class="eyebrow">Devis</span>
                <h1 class="hero-title">Faire passer l interet en demande concrete.</h1>
                <p class="lead">Le visiteur choisit un service, precise sa date, son volume et son budget. La demande est stockee dans la base pour traitement par l equipe.</p>
                <div class="hero-meta-strip">
                    <div class="hero-meta-card">
                        <strong>Service</strong>
                        <span>Le besoin est cadre dès l entree.</span>
                    </div>
                    <div class="hero-meta-card">
                        <strong>Date</strong>
                        <span>Le contexte evenementiel est qualifie.</span>
                    </div>
                    <div class="hero-meta-card">
                        <strong>Budget</strong>
                        <span>L equipe peut prioriser plus vite.</span>
                    </div>
                </div>
            </article>
        </div>
    </section>

    <section class="section">
        <div class="container section-surface">
            <div class="process-grid">
                <article class="process-step">
                    <span class="process-step-index">1</span>
                    <h3>Bon service</h3>
                    <p class="muted" style="margin-top:10px;">Le visiteur entre directement dans le bon univers de prestation.</p>
                </article>
                <article class="process-step">
                    <span class="process-step-index">2</span>
                    <h3>Prix publics comme repere</h3>
                    <p class="muted" style="margin-top:10px;">Le site guide sans promettre un devis ferme trop tot.</p>
                </article>
                <article class="process-step">
                    <span class="process-step-index">3</span>
                    <h3>Traitement interne ensuite</h3>
                    <p class="muted" style="margin-top:10px;">Les informations recueillies permettent un retour equipe plus rapide.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container two-col">
            @include('site.partials.inquiry-form', ['requestType' => 'quote'])

            <article class="info-card">
                <span class="eyebrow">Services</span>
                <h3 style="margin-top:14px;">Points d entree disponibles</h3>
                <div class="mini-grid" style="margin-top:18px; grid-template-columns: 1fr;">
                    @foreach ($servicePages as $servicePage)
                        <div class="mini-card">
                            <strong>{{ $servicePage['name'] }}</strong>
                            <p>{{ $servicePage['headline'] }}</p>
                            <div class="metric-row" style="margin-top:12px;">
                                <span class="metric">{{ $servicePage['stats']['items'] }} ressource(s)</span>
                                @if ($servicePage['startingPrice'])
                                    <span class="metric">{{ $servicePage['startingPrice'] }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        </div>
    </section>
@endsection