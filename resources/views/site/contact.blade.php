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
                    <span class="eyebrow">Contact</span>
                    <h1 class="hero-title">Contactez Queen Park Tunisie</h1>
                    <p class="lead">Une question, une demande d information ou un besoin precis pour votre evenement ? Notre equipe est a votre disposition pour vous repondre dans les meilleurs delais.</p>
                    @if (count($contactPhones) > 0)
                        <div class="metric-row" style="margin-top:18px;">
                            @foreach ($contactPhones as $phone)
                                <span class="metric">{{ $phone }}</span>
                            @endforeach
                        </div>
                    @endif
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container section-surface">
            <div class="process-grid">
                <article class="process-step">
                    <span class="process-step-index">1</span>
                    <h3>Expliquez votre besoin</h3>
                    <p class="muted" style="margin-top:10px;">Partagez les grandes lignes de votre evenement et vos attentes.</p>
                </article>
                <article class="process-step">
                    <span class="process-step-index">2</span>
                    <h3>Choisissez un service</h3>
                    <p class="muted" style="margin-top:10px;">Indiquez le service concerne pour nous aider a vous orienter plus vite.</p>
                </article>
                <article class="process-step">
                    <span class="process-step-index">3</span>
                    <h3>Recevez notre retour</h3>
                    <p class="muted" style="margin-top:10px;">Notre equipe reprend contact avec vous pour avancer sur votre projet.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container two-col">
            @include('site.partials.inquiry-form', ['requestType' => 'contact'])

            <article class="info-card">
                <span class="eyebrow">Services disponibles</span>
                <h3 style="margin-top:14px;">Nos prestations</h3>
                <div class="mini-grid" style="margin-top:18px; grid-template-columns: 1fr;">
                    @foreach ($servicePages as $servicePage)
                        <div class="mini-card">
                            <strong>{{ $servicePage['name'] }}</strong>
                            <p>{{ $servicePage['summary'] }}</p>
                            <div class="stack-actions" style="margin-top:12px;">
                                <a href="{{ route('site.services.show', $servicePage['slug']) }}" class="btn btn-secondary">Voir</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        </div>
    </section>
@endsection