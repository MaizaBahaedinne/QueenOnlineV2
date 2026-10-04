<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? 'Queen Park') . ' | Queen Park' }}</title>
    <meta name="description" content="Queen Park, vitrine de services evenementiels: salles, photo, animation, musique, transport et devis en ligne.">
    <style>
        :root {
            --bg: #ffffff;
            --surface: rgba(255, 255, 255, 0.92);
            --surface-strong: #F8F5FA;
            --surface-dark: #4B1F5C;
            --ink: #22162b;
            --muted: #6f6280;
            --line: rgba(75, 31, 92, 0.14);
            --brand: #4B1F5C;
            --brand-deep: #351342;
            --brand-soft: #8A4FA3;
            --accent: #B47A45;
            --accent-soft: #E8D4BE;
            --forest: #4B1F5C;
            --forest-soft: #F8F5FA;
            --success: #1f7a54;
            --danger: #b7412d;
            --shadow: 0 24px 70px rgba(75, 31, 92, 0.10);
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
                radial-gradient(circle at top left, rgba(180, 122, 69, 0.16), transparent 28%),
                radial-gradient(circle at 85% 15%, rgba(138, 79, 163, 0.12), transparent 26%),
                linear-gradient(180deg, #FFFFFF 0%, #FCFAFD 54%, #F8F5FA 100%);
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
            background: rgba(255, 255, 255, 0.86);
            border-bottom: 1px solid rgba(75, 31, 92, 0.08);
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
            background: linear-gradient(135deg, #D09A60, #B47A45 52%, #946034);
            color: #fffaf5;
            font-weight: 800;
            letter-spacing: 0.06em;
            box-shadow: 0 12px 26px rgba(180, 122, 69, 0.28);
        }
        .brand-wrap { display: flex; align-items: center; gap: 12px; }
        .brand-copy small { display: block; color: var(--muted); text-transform: uppercase; letter-spacing: 0.14em; font-size: 11px; }
        .brand-copy strong { display: block; font-size: 18px; color: var(--brand); }
        .site-nav { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .site-nav a {
            padding: 10px 14px;
            border-radius: 999px;
            color: var(--brand);
        }
        .site-nav a:hover, .site-nav a.is-active {
            background: rgba(75, 31, 92, 0.10);
            color: var(--brand-deep);
        }
        .nav-group {
            position: relative;
            display: inline-flex;
            flex-direction: column;
            align-items: flex-start;
        }
        .nav-trigger {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .nav-trigger::after {
            content: "▾";
            font-size: 12px;
            line-height: 1;
            opacity: 0.8;
        }
        .sub-menu {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            min-width: 230px;
            padding: 10px;
            margin: 0;
            list-style: none;
            border-radius: 16px;
            border: 1px solid rgba(75, 31, 92, 0.14);
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 18px 40px rgba(75, 31, 92, 0.18);
            display: none;
            z-index: 50;
        }
        .sub-menu li {
            padding: 8px 10px;
            border-radius: 10px;
            color: var(--brand);
            font-size: 14px;
        }
        .sub-menu li + li {
            margin-top: 4px;
        }
        .sub-menu li:hover {
            background: rgba(75, 31, 92, 0.08);
        }
        .nav-group:hover .sub-menu,
        .nav-group:focus-within .sub-menu {
            display: block;
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
            background: linear-gradient(135deg, var(--brand), var(--brand-soft));
            color: #fffafc;
            box-shadow: 0 14px 28px rgba(75, 31, 92, 0.22);
        }
        .btn-secondary {
            background: rgba(255, 255, 255, 0.92);
            border-color: rgba(75, 31, 92, 0.14);
            color: var(--brand);
        }
        .page-hero {
            position: relative;
            width: 100%;
            padding: 0;
            overflow: hidden;
            border-bottom: 1px solid rgba(75, 31, 92, 0.08);
        }
        .hero-band {
            position: relative;
            min-height: 640px;
            display: flex;
            align-items: center;
            padding: 42px 0;
            background: linear-gradient(135deg, rgba(75, 31, 92, 0.92), rgba(138, 79, 163, 0.78));
        }
        .hero-slides {
            position: absolute;
            inset: 0;
            overflow: hidden;
        }
        .hero-slide {
            position: absolute;
            inset: 0;
            opacity: 0;
            animation: heroFade 18s infinite;
            transform: scale(1.02);
        }
        .hero-slide::before,
        .hero-slide::after {
            content: "";
            position: absolute;
            inset: 0;
        }
        .hero-slide::before {
            background:
                radial-gradient(circle at 15% 20%, rgba(255,255,255,0.18), transparent 18%),
                radial-gradient(circle at 85% 25%, rgba(180, 122, 69, 0.34), transparent 22%),
                radial-gradient(circle at 55% 85%, rgba(255,255,255,0.12), transparent 18%);
            mix-blend-mode: screen;
        }
        .hero-slide::after {
            background:
                linear-gradient(90deg, rgba(20, 10, 28, 0.74), rgba(20, 10, 28, 0.30) 42%, rgba(20, 10, 28, 0.68)),
                repeating-linear-gradient(120deg, rgba(255,255,255,0.05) 0 2px, transparent 2px 18px);
        }
        .hero-slide-1 {
            background:
                linear-gradient(135deg, rgba(75,31,92,0.88), rgba(138,79,163,0.56)),
                radial-gradient(circle at 20% 30%, rgba(180,122,69,0.44), transparent 24%),
                linear-gradient(120deg, #2e1638 0%, #4B1F5C 38%, #7A4192 100%);
            animation-delay: 0s;
        }
        .hero-slide-2 {
            background:
                linear-gradient(135deg, rgba(52,18,64,0.86), rgba(180,122,69,0.22)),
                radial-gradient(circle at 78% 26%, rgba(255,255,255,0.12), transparent 18%),
                linear-gradient(120deg, #4B1F5C 0%, #5B2C6F 42%, #B47A45 100%);
            animation-delay: 6s;
        }
        .hero-slide-3 {
            background:
                linear-gradient(135deg, rgba(75,31,92,0.82), rgba(138,79,163,0.42)),
                radial-gradient(circle at 70% 70%, rgba(180,122,69,0.28), transparent 22%),
                linear-gradient(120deg, #24102d 0%, #4B1F5C 44%, #8A4FA3 100%);
            animation-delay: 12s;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(180deg, rgba(17, 8, 24, 0.14), rgba(17, 8, 24, 0.42)),
                linear-gradient(90deg, rgba(17, 8, 24, 0.50), rgba(17, 8, 24, 0.14) 38%, rgba(17, 8, 24, 0.54));
        }
        .hero-stage {
            display: block;
            position: relative;
            z-index: 2;
        }
        .hero-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 26px;
            align-items: stretch;
        }
        .hero-panel, .glass-card {
            border: 1px solid rgba(255, 255, 255, 0.18);
            background: rgba(255, 255, 255, 0.14);
            box-shadow: 0 26px 70px rgba(17, 8, 24, 0.24);
            backdrop-filter: blur(16px);
        }
        .hero-panel {
            border-radius: 34px;
            padding: 34px;
            position: relative;
            overflow: hidden;
            isolation: isolate;
            width: min(100%, 860px);
        }
        .hero-panel::after {
            content: "";
            position: absolute;
            inset: auto -40px -60px auto;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(180, 122, 69, 0.32), transparent 70%);
        }
        .hero-panel::before {
            content: "";
            position: absolute;
            inset: 18px auto auto 18px;
            width: 110px;
            height: 110px;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.10);
            background: linear-gradient(135deg, rgba(180, 122, 69, 0.14), rgba(255, 255, 255, 0.06));
            transform: rotate(12deg);
            z-index: -1;
        }
        .hero-panel > * { position: relative; z-index: 1; }
        .eyebrow {
            display: inline-flex;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            font-size: 12px;
            font-weight: 700;
        }
        h1, h2, h3 { margin: 0; line-height: 1.05; }
        h1 { font-size: clamp(42px, 6vw, 76px); margin-top: 18px; letter-spacing: -0.04em; color: #fff; }
        h2 { font-size: clamp(30px, 4vw, 46px); letter-spacing: -0.03em; }
        h3 { font-size: 22px; }
        .hero-title { max-width: 14ch; }
        .lead {
            margin: 18px 0 0;
            font-size: 18px;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.82);
            max-width: 64ch;
        }
        .hero-meta-strip {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 24px;
        }
        .hero-meta-card {
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.14);
        }
        .hero-meta-card strong {
            display: block;
            font-size: 24px;
            letter-spacing: -0.04em;
            color: #fff;
        }
        .hero-meta-card span {
            display: block;
            margin-top: 4px;
            color: rgba(255, 255, 255, 0.72);
            font-size: 13px;
            line-height: 1.5;
        }
        .hero-aside { display: none; }
        .hero-showcase {
            border-radius: 34px;
            padding: 22px;
            background: linear-gradient(180deg, #4B1F5C 0%, #603170 58%, #8A4FA3 100%);
            color: #fff;
            box-shadow: 0 30px 70px rgba(75, 31, 92, 0.26);
            position: relative;
            overflow: hidden;
            min-height: 100%;
        }
        .hero-showcase::after {
            content: "";
            position: absolute;
            right: -28px;
            bottom: -28px;
            width: 180px;
            height: 180px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.16), transparent 68%);
        }
        .hero-showcase > * { position: relative; z-index: 1; }
        .hero-showcase-grid {
            display: grid;
            gap: 12px;
            margin-top: 18px;
        }
        .hero-showcase-card {
            padding: 16px 18px;
            border-radius: 22px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.14);
            backdrop-filter: blur(10px);
        }
        .hero-showcase-card strong,
        .hero-showcase strong {
            color: #fff;
        }
        .hero-showcase p,
        .hero-showcase span,
        .hero-showcase li {
            color: rgba(255, 255, 255, 0.82);
        }
        .showcase-list {
            display: grid;
            gap: 10px;
            margin: 16px 0 0;
            padding: 0;
            list-style: none;
        }
        .showcase-list li {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: baseline;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }
        .showcase-list li:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }
        .hero-secondary-card {
            padding: 20px 22px;
            border-radius: 26px;
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,245,250,0.95));
            border: 1px solid rgba(75, 31, 92, 0.10);
            box-shadow: var(--shadow);
        }
        .hero-secondary-card p {
            margin: 10px 0 0;
            color: var(--muted);
            line-height: 1.7;
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
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(248,245,250,0.96));
            border: 1px solid rgba(75, 31, 92, 0.08);
            box-shadow: var(--shadow);
        }
        .stat-card strong { display: block; font-size: 38px; letter-spacing: -0.04em; }
        .stat-card span { color: var(--muted); }
        .section { padding: 34px 0 12px; }
        .section-surface {
            border-radius: 34px;
            background: linear-gradient(180deg, rgba(248,245,250,0.70), rgba(255,255,255,0.92));
            border: 1px solid rgba(75, 31, 92, 0.08);
            padding: 24px;
            box-shadow: var(--shadow);
        }
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
            border: 1px solid rgba(75, 31, 92, 0.08);
            box-shadow: var(--shadow);
        }
        .service-card { padding: 22px; display: grid; gap: 16px; transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
        .service-card:hover {
            transform: translateY(-4px);
            border-color: rgba(138, 79, 163, 0.22);
            box-shadow: 0 28px 56px rgba(75, 31, 92, 0.14);
        }
        .service-card-top { display: flex; justify-content: space-between; gap: 14px; align-items: start; }
        .chip {
            display: inline-flex;
            align-items: center;
            padding: 7px 11px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: #8C5B2A;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .price-badge {
            display: inline-flex;
            padding: 9px 12px;
            border-radius: 999px;
            background: rgba(75, 31, 92, 0.10);
            color: var(--forest);
            font-weight: 700;
        }
        .service-card p, .info-card p, .feature-card p, .form-copy p { margin: 0; color: var(--muted); line-height: 1.7; }
        .metric-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .metric { padding: 8px 10px; border-radius: 12px; background: rgba(255,255,255,0.8); border: 1px solid rgba(75, 31, 92, 0.10); font-size: 14px; color: #413064; }
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
            background: linear-gradient(135deg, var(--accent), var(--brand));
            flex: 0 0 auto;
        }
        .room-grid, .mini-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .room-card, .mini-card { padding: 20px; border-radius: var(--radius-lg); background: rgba(255,255,255,0.95); border: 1px solid rgba(75, 31, 92, 0.08); }
        .room-card strong, .mini-card strong { display: block; font-size: 20px; margin-bottom: 10px; }
        .hero-room-card {
            border-radius: 22px;
            padding: 18px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.14);
        }
        .hero-room-card strong {
            display: block;
            font-size: 22px;
            margin-bottom: 8px;
            color: #fff;
        }
        .hero-room-card p { margin: 0; color: rgba(255,255,255,0.82); line-height: 1.6; }
        .muted { color: var(--muted); }
        .two-col { display: grid; grid-template-columns: 1fr 0.95fr; gap: 22px; }
        .process-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }
        .process-step {
            padding: 22px;
            border-radius: 24px;
            background: rgba(255,255,255,0.94);
            border: 1px solid rgba(75, 31, 92, 0.08);
            box-shadow: var(--shadow);
        }
        .process-step-index {
            display: inline-grid;
            place-items: center;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--accent), #d39a63);
            color: #fff;
            font-weight: 800;
            margin-bottom: 12px;
        }
        .cta-panel {
            border-radius: 34px;
            padding: 28px;
            background: linear-gradient(135deg, rgba(75,31,92,0.98), rgba(138,79,163,0.94));
            color: #fff;
            box-shadow: 0 30px 70px rgba(75, 31, 92, 0.24);
        }
        .cta-panel p { margin: 10px 0 0; color: rgba(255,255,255,0.82); line-height: 1.7; }

        @keyframes heroFade {
            0% { opacity: 0; transform: scale(1.02); }
            6% { opacity: 1; transform: scale(1); }
            28% { opacity: 1; transform: scale(1.03); }
            34% { opacity: 0; transform: scale(1.05); }
            100% { opacity: 0; }
        }
        .form-card, .info-card { padding: 24px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .field { display: grid; gap: 8px; }
        .field.full { grid-column: 1 / -1; }
        .field label { font-size: 14px; font-weight: 700; color: var(--brand); }
        .field input, .field select, .field textarea {
            width: 100%;
            padding: 13px 14px;
            border-radius: 14px;
            border: 1px solid rgba(75, 31, 92, 0.14);
            background: rgba(255,255,255,0.96);
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
            background: linear-gradient(135deg, #4B1F5C, #8A4FA3);
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: center;
            box-shadow: 0 26px 60px rgba(75, 31, 92, 0.22);
        }
        .quote-band p { margin: 8px 0 0; color: rgba(255, 255, 255, 0.78); }
        .site-footer { padding: 38px 0 56px; color: #5e5852; }
        .footer-row { display: flex; justify-content: space-between; gap: 18px; align-items: center; flex-wrap: wrap; border-top: 1px solid rgba(75, 31, 92, 0.10); padding-top: 18px; }
        .empty-state {
            padding: 22px;
            border-radius: 20px;
            border: 1px dashed rgba(75, 31, 92, 0.18);
            background: rgba(255,255,255,0.72);
            color: var(--muted);
        }

        @media (max-width: 1024px) {
            .hero-grid, .two-col, .card-grid, .room-grid, .mini-grid, .process-grid { grid-template-columns: 1fr; }
            .header-row { align-items: start; flex-direction: column; }
            .site-nav, .header-cta { width: 100%; }
            .nav-group { width: 100%; }
            .sub-menu {
                position: static;
                display: block;
                width: 100%;
                margin-top: 6px;
                box-shadow: none;
            }
            .hero-meta-strip { grid-template-columns: 1fr; }
            .hero-band { min-height: 580px; }
        }

        @media (max-width: 720px) {
            .container { width: min(calc(100% - 24px), var(--content)); }
            .hero-panel, .form-card, .info-card, .service-card { padding: 20px; }
            .form-grid, .stats-grid { grid-template-columns: 1fr; }
            .field.full { grid-column: auto; }
            h1 { font-size: 38px; }
            .quote-band { flex-direction: column; align-items: start; }
            .section-surface, .cta-panel, .hero-showcase, .hero-secondary-card { padding: 20px; }
            .hero-band { min-height: 520px; padding: 28px 0; }
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
                    </span>
                </a>

                @php $currentRoute = request()->route()?->getName(); @endphp
                <nav class="site-nav" aria-label="Navigation principale">
                    <a href="{{ route('site.home') }}" class="{{ $currentRoute === 'site.home' ? 'is-active' : '' }}">Accueil</a>
                    <a href="{{ route('site.about') }}" class="{{ $currentRoute === 'site.about' ? 'is-active' : '' }}">A propos</a>
                    <div class="nav-group">
                        <a href="{{ route('site.services.index') }}" class="nav-trigger {{ str_starts_with((string) $currentRoute, 'site.services') ? 'is-active' : '' }}">Services</a>
                        @if (! empty($serviceOptions ?? []))
                            <ul class="sub-menu" aria-label="Services proposes">
                                @foreach ($serviceOptions as $serviceOption)
                                    <li>{{ $serviceOption['name'] }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
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