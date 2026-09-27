@extends('layouts.public')

@section('content')
    <section class="page-hero">
        <div class="container two-col">
            <article class="hero-panel">
                <span class="eyebrow">Contact</span>
                <h1>Parler a Queen Park a partir du site public.</h1>
                <p class="lead">Le formulaire enregistre une demande directement dans la base du projet. Tu peux aussi orienter les visiteurs vers les services deja actifs sur la plateforme.</p>
                @if (count($contactPhones) > 0)
                    <div class="metric-row" style="margin-top:18px;">
                        @foreach ($contactPhones as $phone)
                            <span class="metric">{{ $phone }}</span>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="info-card">
                <h3>Ce que ce formulaire permet</h3>
                <ul class="bullet-list" style="margin-top:16px;">
                    <li>Centraliser les premiers messages visiteurs dans la meme base que la plateforme.</li>
                    <li>Associer un service concerne des le premier contact.</li>
                    <li>Preparer une future interface admin de suivi des demandes.</li>
                </ul>
            </article>
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