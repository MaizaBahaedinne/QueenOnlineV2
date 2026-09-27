@extends('layouts.public')

@section('content')
    <section class="page-hero">
        <div class="container two-col">
            <article class="hero-panel">
                <span class="eyebrow">Devis</span>
                <h1>Recuperer une demande commerciale sans ressaisie.</h1>
                <p class="lead">Le visiteur choisit un service, precise sa date, son volume et son budget. La demande est stockee dans la base pour traitement par l equipe.</p>
            </article>
            <article class="info-card">
                <h3>Conseil d usage</h3>
                <ul class="bullet-list" style="margin-top:16px;">
                    <li>Oriente le visiteur vers le bon service avant la prise de contact.</li>
                    <li>Utilise les prix d entree comme repere, pas comme devis ferme.</li>
                    <li>Complete plus tard avec workflow admin et attribution interne.</li>
                </ul>
            </article>
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