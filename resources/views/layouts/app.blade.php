<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? 'Dashboard') . ' - QueenPark Admin' }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        :root {
            --bg: #f3f5f9;
            --surface: #ffffff;
            --surface-soft: #f9fafc;
            --ink: #0f172a;
            --muted: #64748b;
            --primary: #2463eb;
            --primary-dark: #1b4fbc;
            --accent: #14b8a6;
            --line: #e2e8f0;
            --danger: #dc2626;
            --warning: #d97706;
            --success: #059669;
            --shadow: 0 10px 30px rgba(17, 24, 39, 0.08);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: var(--ink);
            background:
                radial-gradient(circle at 20% 0%, #dde8ff 0, transparent 40%),
                radial-gradient(circle at 90% 100%, #d8f7f2 0, transparent 45%),
                var(--bg);
            font-family: "Segoe UI", "Trebuchet MS", Arial, sans-serif;
        }

        .app-shell { min-height: 100vh; display: grid; grid-template-columns: 280px 1fr; }

        .sidebar {
            background: linear-gradient(180deg, #0f172a 0%, #111c36 100%);
            color: #d9e5ff;
            padding: 20px 16px;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; padding: 6px 8px; }

        .brand-badge {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: linear-gradient(135deg, #4f8cff, #1de7c2);
            display: grid;
            place-items: center;
            color: #082f49;
            font-weight: 800;
        }

        .brand-text { font-weight: 700; letter-spacing: 0.2px; color: #f8fbff; }
        .menu-title { margin: 14px 10px 8px; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; color: #94a3b8; }
        .menu { list-style: none; margin: 0; padding: 0; }

        .menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #cbd5e1;
            padding: 11px 12px;
            margin: 4px 6px;
            border-radius: 10px;
            transition: .2s ease;
            border: 1px solid transparent;
        }

        .menu a:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(148, 163, 184, 0.25);
            color: #fff;
            transform: translateX(2px);
        }

        .menu a.active {
            background: linear-gradient(120deg, rgba(36, 99, 235, 0.35), rgba(20, 184, 166, 0.35));
            border-color: rgba(125, 211, 252, 0.35);
            color: #fff;
        }

        .main { padding: 18px; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 16px; }

        .search {
            flex: 1;
            max-width: 420px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 10px 12px;
            color: var(--muted);
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.03);
        }

        .profile {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 8px 12px;
            font-size: 13px;
            color: var(--muted);
        }

        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 14px; padding: 16px; box-shadow: var(--shadow); }
        .panel-title { margin: 0 0 6px; font-size: 19px; }
        .panel-sub { margin: 0; color: var(--muted); font-size: 14px; }

        .kpi-grid { margin-top: 16px; display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }

        .kpi-card { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 14px; }
        .kpi-label { font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 8px; }
        .kpi-value { font-size: 26px; font-weight: 700; line-height: 1; }
        .kpi-foot { margin-top: 7px; font-size: 12px; color: var(--muted); }

        .content-grid { margin-top: 14px; display: grid; grid-template-columns: 1fr; gap: 12px; }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
        }

        th, td { text-align: left; padding: 12px; border-bottom: 1px solid var(--line); font-size: 14px; }

        th {
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            background: var(--surface-soft);
        }

        tr:last-child td { border-bottom: none; }

        .badge { display: inline-block; border-radius: 999px; padding: 4px 10px; font-size: 12px; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }

        .page-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 12px; }

        .btn {
            display: inline-block;
            text-decoration: none;
            padding: 9px 12px;
            border-radius: 10px;
            border: 1px solid var(--line);
            color: var(--ink);
            background: var(--surface);
            font-size: 14px;
        }

        .btn-primary { background: linear-gradient(120deg, var(--primary), #3b82f6); color: #fff; border-color: var(--primary-dark); }
        .muted { color: var(--muted); }

        @media (max-width: 1100px) {
            .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 900px) {
            .app-shell { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
            .topbar { flex-direction: column; align-items: stretch; }
            .search { max-width: none; }
        }

        @media (max-width: 640px) {
            .kpi-grid { grid-template-columns: 1fr; }
            th, td { padding: 10px; }
        }
    </style>
</head>
<body>
    @php $current = request()->route()?->getName(); @endphp

    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-badge">Q</div>
                <div class="brand-text">QueenPark Admin</div>
            </div>

            <div class="menu-title">Navigation</div>
            <ul class="menu">
                <li><a class="{{ $current === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}">Dashboard</a></li>
                <li><a class="{{ str_starts_with((string) $current, 'users.') ? 'active' : '' }}" href="{{ route('users.index') }}">Utilisateurs</a></li>
                <li><a class="{{ str_starts_with((string) $current, 'clients.') ? 'active' : '' }}" href="{{ route('clients.index') }}">Clients</a></li>
                <li><a class="{{ str_starts_with((string) $current, 'salles.') ? 'active' : '' }}" href="{{ route('salles.index') }}">Salles</a></li>
                <li><a class="{{ str_starts_with((string) $current, 'reservations.') ? 'active' : '' }}" href="{{ route('reservations.index') }}">Reservations</a></li>
                <li><a class="{{ str_starts_with((string) $current, 'payments.') ? 'active' : '' }}" href="{{ route('payments.index') }}">Paiements</a></li>
            </ul>
        </aside>

        <main class="main">
            <div class="topbar">
                <div class="search">Workspace: QueenOnlineV2</div>
                <div class="profile">Production panel • {{ now()->format('d/m/Y H:i') }}</div>
            </div>

            @yield('content')
        </main>
    </div>
</body>
</html>
