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
                    <span class="eyebrow">A propos</span>
                    <h1 class="hero-title">Queen Park Tunisie, un lieu pense pour celebrer avec elegance.</h1>
                    <p class="lead">Queen Park accompagne les familles et les organisateurs dans la preparation de leurs receptions avec un cadre raffine, des services soignes et une equipe attentive a chaque detail.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container section-surface">
            <div class="process-grid">
                <article class="process-step">
                    <span class="process-step-index">1</span>
                    <h3>{{ $siteStats['active_rooms'] }} salle(s) a decouvrir</h3>
                    <p class="muted" style="margin-top:10px;">Des espaces adaptes a differents styles de receptions et capacites d accueil.</p>
                </article>
                <article class="process-step">
                    <span class="process-step-index">2</span>
                    <h3>{{ $siteStats['active_items'] }} prestation(s) disponibles</h3>
                    <p class="muted" style="margin-top:10px;">Des options pour enrichir votre evenement selon vos envies et votre budget.</p>
                </article>
                <article class="process-step">
                    <span class="process-step-index">3</span>
                    <h3>{{ $siteStats['active_packs'] }} formule(s) proposee(s)</h3>
                    <p class="muted" style="margin-top:10px;">Des combinaisons pensees pour simplifier votre choix et mieux vous orienter.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container section-surface card-grid">
            <article class="info-card">
                <span class="eyebrow">1. Notre promesse</span>
                <h3 style="margin-top:14px;">Un accueil soigne pour chaque celebration</h3>
                <p style="margin-top:12px;">Mariages, fiancailles, receptions et evenements prives meritent un lieu qui conjugue charme, confort et sens du detail.</p>
            </article>
            <article class="info-card">
                <span class="eyebrow">2. Notre accompagnement</span>
                <h3 style="margin-top:14px;">Une equipe a votre ecoute</h3>
                <p style="margin-top:12px;">De la premiere demande d information jusqu au devis, nous vous orientons avec clarte et disponibilite.</p>
            </article>
            <article class="info-card">
                <span class="eyebrow">3. Notre univers</span>
                <h3 style="margin-top:14px;">Des prestations pour completer votre evenement</h3>
                <p style="margin-top:12px;">Animation, musique, photo, transport ou formalites: Queen Park rassemble plusieurs services pour vous offrir une experience plus complete.</p>
            </article>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="quote-band">
                <div>
                    <strong>Parlons de votre evenement</strong>
                    <p>Notre equipe est a votre disposition pour vous renseigner et preparer une proposition adaptee a vos attentes.</p>
                </div>
                <a href="{{ route('site.quote') }}" class="btn btn-secondary">Demander un devis</a>
            </div>
        </div>
    </section>
@endsection