<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? 'Dashboard') . ' - QueenPark Admin' }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

</head>
<body class="admin-body">
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
                <button type="button" class="btn sidebar-toggle" data-sidebar-toggle>Menu</button>
                <div class="search">Workspace: QueenOnlineV2</div>
                <div class="profile">Production panel • {{ now()->format('d/m/Y H:i') }}</div>
            </div>

            @yield('content')
        </main>
    </div>
</body>
</html>
