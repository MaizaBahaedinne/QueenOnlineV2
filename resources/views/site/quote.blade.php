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
            <aside class="hero-aside">
                <article class="hero-showcase">
                    <span class="eyebrow" style="background:rgba(255,255,255,0.14); color:#fff;">Conseil</span>
                    <h3 style="margin-top:14px;">Cadre de reponse</h3>
                    <ul class="showcase-list">
                        <li><span>Bon service</span><strong>d abord</strong></li>
                        <li><span>Prix publics</span><strong>repere</strong></li>
                        <li><span>Traitement interne</span><strong>ensuite</strong></li>
                    </ul>
                </article>
            </aside>
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