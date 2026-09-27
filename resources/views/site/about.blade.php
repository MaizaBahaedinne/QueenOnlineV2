@extends('layouts.public')

@section('content')
    <section class="page-hero">
        <div class="container hero-stage">
            <article class="hero-panel">
                <span class="eyebrow">A propos</span>
                <h1 class="hero-title">Queen Park aligne enfin la vitrine et l exploitation.</h1>
                <p class="lead">Le site public n est pas une vitrine deconnectee. Il reprend les services que vous avez deja structures dans la plateforme afin de limiter les doublons, garder des prix cohérents et simplifier la prise de contact.</p>
            </article>
            <aside class="hero-aside">
                <article class="hero-showcase">
                    <span class="eyebrow" style="background:rgba(255,255,255,0.14); color:#fff;">Base existante</span>
                    <h3 style="margin-top:14px;">Ce que la vitrine consomme deja</h3>
                    <ul class="showcase-list">
                        <li><span>Salles actives</span><strong>{{ $siteStats['active_rooms'] }}</strong></li>
                        <li><span>Ressources services</span><strong>{{ $siteStats['active_items'] }}</strong></li>
                        <li><span>Packs affichables</span><strong>{{ $siteStats['active_packs'] }}</strong></li>
                    </ul>
                </article>
            </aside>
        </div>
    </section>

    <section class="section">
        <div class="container section-surface card-grid">
            <article class="info-card">
                <span class="eyebrow">1. Source unique</span>
                <h3 style="margin-top:14px;">Les donnees viennent de la plateforme</h3>
                <p style="margin-top:12px;">Les services presentes ici derivent des tables deja maintenues par vos equipes: salles, items de service et packs actifs.</p>
            </article>
            <article class="info-card">
                <span class="eyebrow">2. Demandes tracables</span>
                <h3 style="margin-top:14px;">Contact et devis centralises</h3>
                <p style="margin-top:12px;">Les formulaires publics alimentent une table de demandes dediee afin de preparer une future interface de suivi interne.</p>
            </article>
            <article class="info-card">
                <span class="eyebrow">3. Evolution simple</span>
                <h3 style="margin-top:14px;">Chaque service peut grandir page par page</h3>
                <p style="margin-top:12px;">La structure est deja prete pour des contenus plus riches: FAQ, temoignages, galeries ou parametres admin plus tard.</p>
            </article>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="quote-band">
                <div>
                    <strong>Besoin d un parcours client plus riche ?</strong>
                    <p>La base est posee: pages publiques, formulaires stockes en base et passerelle directe vers l espace de connexion.</p>
                </div>
                <a href="{{ route('site.quote') }}" class="btn btn-secondary">Demander un devis</a>
            </div>
        </div>
    </section>
@endsection