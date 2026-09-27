<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? 'Queen Park') . ' | Queen Park' }}</title>
    <meta name="description" content="Queen Park, vitrine de services evenementiels: salles, photo, animation, musique, transport et devis en ligne.">
    <style>
        :root {
            --bg: #f7f1e8;
            --surface: rgba(255, 252, 247, 0.82);
            --surface-strong: #fffdf9;
            --surface-dark: #16302b;
            --ink: #1d1917;
            --muted: #6e655f;
            --line: rgba(52, 38, 26, 0.12);
            --brand: #a44f2d;
            --brand-deep: #7d351d;
            --accent: #d7b067;
            --accent-soft: #f0e0bb;
            --forest: #25453e;
            --forest-soft: #dfeeea;
            --success: #1f7a54;
            --danger: #b7412d;
            --shadow: 0 24px 70px rgba(38, 21, 8, 0.10);
            --radius-xl: 28px;
            --radius-lg: 22px;
            --radius-md: 16px;
            --content: 1180px;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: "Trebuchet MS", "Avenir Next", "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at top left, rgba(215, 176, 103, 0.30), transparent 33%),
                radial-gradient(circle at 85% 15%, rgba(37, 69, 62, 0.14), transparent 28%),
                linear-gradient(180deg, #f9f4eb 0%, #f3ece1 52%, #efe6d9 100%);
        }

        a { color: inherit; text-decoration: none; }
        img { max-width: 100%; display: block; }
        button, input, select, textarea { font: inherit; }

        .shell { min-height: 100vh; }
        .container { width: min(calc(100% - 32px), var(--content)); margin: 0 auto; }
        .site-header {
            position: sticky;
            top: 0;
            z-index: 40;
            backdrop-filter: blur(16px);
            background: rgba(247, 241, 232, 0.74);
            border-bottom: 1px solid rgba(52, 38, 26, 0.08);
        }
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            padding: 14px 0;
        }
        .brand-mark {
            display: inline-grid;
            place-items: center;
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--brand), #d17b42);
            color: #fff7ef;
            font-weight: 800;
            letter-spacing: 0.06em;
            box-shadow: 0 12px 26px rgba(125, 53, 29, 0.25);
        }
        .brand-wrap { display: flex; align-items: center; gap: 12px; }
        .brand-copy small { display: block; color: var(--muted); text-transform: uppercase; letter-spacing: 0.14em; font-size: 11px; }
        .brand-copy strong { display: block; font-size: 18px; }
        .site-nav { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .site-nav a {
            padding: 10px 14px;
            border-radius: 999px;
            color: #3a2b20;
        }
        .site-nav a:hover, .site-nav a.is-active {
            background: rgba(164, 79, 45, 0.10);
            color: var(--brand-deep);
        }
        .header-cta { display: flex; align-items: center; gap: 10px; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 18px;
            border-radius: 999px;
            border: 1px solid transparent;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
            cursor: pointer;
        }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary {
            background: linear-gradient(135deg, var(--brand), #ca7140);
            color: #fffaf4;
            box-shadow: 0 14px 28px rgba(164, 79, 45, 0.22);
        }
        .btn-secondary {
            background: rgba(255, 253, 249, 0.7);
            border-color: rgba(52, 38, 26, 0.10);
            color: #2e231d;
        }
        .page-hero {
            padding: 56px 0 26px;
        }
        .hero-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 26px;
            align-items: stretch;
        }
        .hero-panel, .glass-card {
            border: 1px solid rgba(52, 38, 26, 0.08);
            background: var(--surface);
            box-shadow: var(--shadow);
            backdrop-filter: blur(14px);
        }
        .hero-panel {
            border-radius: 34px;
            padding: 34px;
            position: relative;
            overflow: hidden;
        }
        .hero-panel::after {
            content: "";
            position: absolute;
            inset: auto -40px -60px auto;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(215, 176, 103, 0.30), transparent 70%);
        }
        .eyebrow {
            display: inline-flex;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(37, 69, 62, 0.10);
            color: var(--forest);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 12px;
            font-weight: 700;
        }
        h1, h2, h3 { margin: 0; line-height: 1.05; }
        h1 { font-size: clamp(42px, 6vw, 76px); margin-top: 18px; letter-spacing: -0.04em; }
        h2 { font-size: clamp(30px, 4vw, 46px); letter-spacing: -0.03em; }
        h3 { font-size: 22px; }
        .lead {
            margin: 18px 0 0;
            font-size: 18px;
            line-height: 1.7;
            color: #504743;
            max-width: 64ch;
        }
        .hero-actions, .stack-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .stat-card {
            border-radius: var(--radius-xl);
            padding: 22px;
            background: linear-gradient(180deg, rgba(255,255,255,0.85), rgba(252,247,239,0.88));
            border: 1px solid rgba(52, 38, 26, 0.08);
            box-shadow: var(--shadow);
        }
        .stat-card strong { display: block; font-size: 38px; letter-spacing: -0.04em; }
        .stat-card span { color: var(--muted); }
        .section { padding: 34px 0 12px; }
        .section-head { display: flex; align-items: end; justify-content: space-between; gap: 20px; margin-bottom: 22px; }
        .section-head p { margin: 10px 0 0; color: var(--muted); max-width: 60ch; line-height: 1.7; }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }
        .feature-card, .service-card, .form-card, .info-card {
            border-radius: var(--radius-xl);
            background: var(--surface);
            border: 1px solid rgba(52, 38, 26, 0.08);
            box-shadow: var(--shadow);
        }
        .service-card { padding: 22px; display: grid; gap: 16px; }
        .service-card-top { display: flex; justify-content: space-between; gap: 14px; align-items: start; }
        .chip {
            display: inline-flex;
            align-items: center;
            padding: 7px 11px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: #6a4416;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .price-badge {
            display: inline-flex;
            padding: 9px 12px;
            border-radius: 999px;
            background: rgba(37, 69, 62, 0.10);
            color: var(--forest);
            font-weight: 700;
        }
        .service-card p, .info-card p, .feature-card p, .form-copy p { margin: 0; color: var(--muted); line-height: 1.7; }
        .metric-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .metric { padding: 8px 10px; border-radius: 12px; background: rgba(255,255,255,0.6); border: 1px solid rgba(52, 38, 26, 0.08); font-size: 14px; color: #41352f; }
        .bullet-list { display: grid; gap: 10px; margin: 0; padding: 0; list-style: none; }
        .bullet-list li {
            display: flex;
            align-items: start;
            gap: 10px;
            color: #352b26;
            line-height: 1.6;
        }
        .bullet-list li::before {
            content: "";
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-top: 8px;
            background: linear-gradient(135deg, var(--brand), var(--accent));
            flex: 0 0 auto;
        }
        .room-grid, .mini-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .room-card, .mini-card { padding: 20px; border-radius: var(--radius-lg); background: rgba(255,255,255,0.72); border: 1px solid rgba(52, 38, 26, 0.08); }
        .room-card strong, .mini-card strong { display: block; font-size: 20px; margin-bottom: 10px; }
        .muted { color: var(--muted); }
        .two-col { display: grid; grid-template-columns: 1fr 0.95fr; gap: 22px; }
        .form-card, .info-card { padding: 24px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .field { display: grid; gap: 8px; }
        .field.full { grid-column: 1 / -1; }
        .field label { font-size: 14px; font-weight: 700; color: #3b3029; }
        .field input, .field select, .field textarea {
            width: 100%;
            padding: 13px 14px;
            border-radius: 14px;
            border: 1px solid rgba(52, 38, 26, 0.14);
            background: rgba(255,255,255,0.8);
            color: var(--ink);
        }
        .field textarea { min-height: 140px; resize: vertical; }
        .flash, .error-box {
            padding: 14px 16px;
            border-radius: 16px;
            margin-bottom: 18px;
        }
        .flash { background: rgba(31, 122, 84, 0.12); color: var(--success); border: 1px solid rgba(31, 122, 84, 0.18); }
        .error-box { background: rgba(183, 65, 45, 0.10); color: var(--danger); border: 1px solid rgba(183, 65, 45, 0.18); }
        .error-box ul { margin: 8px 0 0 18px; padding: 0; }
        .quote-band {
            margin-top: 26px;
            padding: 22px;
            border-radius: 30px;
            background: linear-gradient(135deg, #17342e, #295148);
            color: #eff7f4;
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: center;
            box-shadow: 0 26px 60px rgba(22, 48, 43, 0.22);
        }
        .quote-band p { margin: 8px 0 0; color: rgba(239, 247, 244, 0.78); }
        .site-footer { padding: 38px 0 56px; color: #5e5852; }
        .footer-row { display: flex; justify-content: space-between; gap: 18px; align-items: center; flex-wrap: wrap; border-top: 1px solid rgba(52, 38, 26, 0.10); padding-top: 18px; }
        .empty-state {
            padding: 22px;
            border-radius: 20px;
            border: 1px dashed rgba(52, 38, 26, 0.18);
            background: rgba(255,255,255,0.46);
            color: var(--muted);
        }

        @media (max-width: 1024px) {
            .hero-grid, .two-col, .card-grid, .room-grid, .mini-grid { grid-template-columns: 1fr; }
            .header-row { align-items: start; flex-direction: column; }
            .site-nav, .header-cta { width: 100%; }
        }

        @media (max-width: 720px) {
            .container { width: min(calc(100% - 24px), var(--content)); }
            .hero-panel, .form-card, .info-card, .service-card { padding: 20px; }
            .form-grid, .stats-grid { grid-template-columns: 1fr; }
            .field.full { grid-column: auto; }
            h1 { font-size: 38px; }
            .quote-band { flex-direction: column; align-items: start; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <header class="site-header">
            <div class="container header-row">
                <a href="{{ route('site.home') }}" class="brand-wrap">
                    <span class="brand-mark">QP</span>
                    <span class="brand-copy">
                        <small>Queen Park</small>
                        <strong>Maison de services evenementiels</strong>
                    </span>
                </a>

                @php $currentRoute = request()->route()?->getName(); @endphp
                <nav class="site-nav" aria-label="Navigation principale">
                    <a href="{{ route('site.home') }}" class="{{ $currentRoute === 'site.home' ? 'is-active' : '' }}">Accueil</a>
                    <a href="{{ route('site.about') }}" class="{{ $currentRoute === 'site.about' ? 'is-active' : '' }}">A propos</a>
                    <a href="{{ route('site.services.index') }}" class="{{ str_starts_with((string) $currentRoute, 'site.services') ? 'is-active' : '' }}">Services</a>
                    <a href="{{ route('site.contact') }}" class="{{ $currentRoute === 'site.contact' ? 'is-active' : '' }}">Contact</a>
                </nav>

                <div class="header-cta">
                    <a href="{{ route('site.quote') }}" class="btn btn-primary">Obtenir un devis</a>
                    <a href="{{ route('login') }}" class="btn btn-secondary">Se connecter</a>
                </div>
            </div>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="site-footer">
            <div class="container footer-row">
                <div>
                    <strong>Queen Park</strong>
                    <div class="muted">Vitrine alimentee par les donnees services de la plateforme.</div>
                </div>
                <div class="muted">Accueil, services, demandes de devis et connexion sur une meme base.</div>
            </div>
        </footer>
    </div>
</body>
</html>