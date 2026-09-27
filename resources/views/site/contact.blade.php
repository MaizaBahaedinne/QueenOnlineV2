@extends('layouts.public')

@section('content')
    <section class="page-hero">
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

            <aside class="hero-aside">
                <article class="hero-showcase">
                    <span class="eyebrow" style="background:rgba(255,255,255,0.14); color:#fff;">Usage</span>
                    <h3 style="margin-top:14px;">Ce que ce formulaire permet</h3>
                    <ul class="showcase-list">
                        <li><span>Centraliser les premiers messages</span><strong>oui</strong></li>
                        <li><span>Associer un service des le depart</span><strong>oui</strong></li>
                        <li><span>Preparer le suivi interne</span><strong>oui</strong></li>
                    </ul>
                </article>
            </aside>
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