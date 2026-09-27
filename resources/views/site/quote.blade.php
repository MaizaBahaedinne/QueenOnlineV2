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
                    <span class="eyebrow">Devis</span>
                    <h1 class="hero-title">Obtenez votre devis Queen Park</h1>
                    <p class="lead">Indiquez le service souhaite, la date de votre evenement et les informations utiles. Notre equipe vous recontactera avec une proposition adaptee.</p>
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
        </div>
    </section>

    <section class="section">
        <div class="container section-surface">
            <div class="process-grid">
                <article class="process-step">
                    <span class="process-step-index">1</span>
                    <h3>Selectionnez votre service</h3>
                    <p class="muted" style="margin-top:10px;">Choisissez l univers qui correspond a votre evenement.</p>
                </article>
                <article class="process-step">
                    <span class="process-step-index">2</span>
                    <h3>Precisez votre besoin</h3>
                    <p class="muted" style="margin-top:10px;">Ajoutez les informations essentielles pour orienter notre proposition.</p>
                </article>
                <article class="process-step">
                    <span class="process-step-index">3</span>
                    <h3>Recevez notre retour</h3>
                    <p class="muted" style="margin-top:10px;">Nous revenons vers vous rapidement avec une reponse claire et personnalisee.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container two-col">
            @include('site.partials.inquiry-form', ['requestType' => 'quote'])

            <article class="info-card">
                <span class="eyebrow">Services</span>
                <h3 style="margin-top:14px;">Prestations disponibles</h3>
                <div class="mini-grid" style="margin-top:18px; grid-template-columns: 1fr;">
                    @foreach ($servicePages as $servicePage)
                        <div class="mini-card">
                            <strong>{{ $servicePage['name'] }}</strong>
                            <p>{{ $servicePage['headline'] }}</p>
                            <div class="metric-row" style="margin-top:12px;">
                                <span class="metric">{{ $servicePage['stats']['items'] }} option(s)</span>
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