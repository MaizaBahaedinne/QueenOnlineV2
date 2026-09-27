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
                    <h1 class="hero-title">Parler a Queen Park sans perdre le visiteur en route.</h1>
                    <p class="lead">Le formulaire enregistre une demande directement dans la base du projet. Tu peux aussi orienter les visiteurs vers les services deja actifs sur la plateforme.</p>
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
                    <h3>Centraliser les messages</h3>
                    <p class="muted" style="margin-top:10px;">Les premiers contacts visiteurs sont stockes dans le meme projet.</p>
                </article>
                <article class="process-step">
                    <span class="process-step-index">2</span>
                    <h3>Associer un service</h3>
                    <p class="muted" style="margin-top:10px;">Le contexte est mieux qualifie avant meme la reponse de l equipe.</p>
                </article>
                <article class="process-step">
                    <span class="process-step-index">3</span>
                    <h3>Preparer le suivi</h3>
                    <p class="muted" style="margin-top:10px;">La base est prete pour une future interface admin de traitement.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container two-col">
            @include('site.partials.inquiry-form', ['requestType' => 'contact'])

            <article class="info-card">
                <span class="eyebrow">Services disponibles</span>
                <h3 style="margin-top:14px;">Pages publiques deja reliees</h3>
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