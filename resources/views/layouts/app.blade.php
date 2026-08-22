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
    @php
        $current = request()->route()?->getName();
        $currentModuleSlug = request()->route('module');
        $isPilotage = $current === 'dashboard';
        $isExploitation = str_starts_with((string) $current, 'clients.')
            || str_starts_with((string) $current, 'salles.')
            || str_starts_with((string) $current, 'reservations.')
            || str_starts_with((string) $current, 'payments.');
        $isAdministration = str_starts_with((string) $current, 'users.')
            || str_starts_with((string) $current, 'roles.')
            || str_starts_with((string) $current, 'modules.')
            || str_starts_with((string) $current, 'permissions.');
        $isServices = in_array((string) $currentModuleSlug, ['troupe-musicale', 'photographe', 'chanteur', 'notaire', 'animation', 'voiture'], true);
    @endphp

    <div class="app-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-badge">Q</div>
                <div class="brand-text">QueenPark Admin</div>
            </div>

            <div class="menu-section {{ $isPilotage ? 'is-open' : '' }}" data-menu-section>
                <button type="button" class="menu-section-toggle" data-menu-toggle>
                    <span class="menu-title">Pilotage</span>
                    <span class="menu-chevron">+</span>
                </button>
                <div class="menu-section-content">
                    <ul class="menu">
                        <li><a class="{{ $current === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="menu-icon">DB</span><span>Dashboard</span></a></li>
                    </ul>
                </div>
            </div>

            <div class="menu-section {{ $isExploitation ? 'is-open' : '' }}" data-menu-section>
                <button type="button" class="menu-section-toggle" data-menu-toggle>
                    <span class="menu-title">Exploitation</span>
                    <span class="menu-chevron">+</span>
                </button>
                <div class="menu-section-content">
                    <ul class="menu">
                        <li><a class="{{ str_starts_with((string) $current, 'clients.') ? 'active' : '' }}" href="{{ route('clients.index') }}"><span class="menu-icon">CL</span><span>Clients</span></a></li>
                        <li><a class="{{ str_starts_with((string) $current, 'salles.') ? 'active' : '' }}" href="{{ route('salles.index') }}"><span class="menu-icon">SA</span><span>Salles</span></a></li>
                        <li><a class="{{ str_starts_with((string) $current, 'reservations.') ? 'active' : '' }}" href="{{ route('reservations.index') }}"><span class="menu-icon">RS</span><span>Reservations</span></a></li>
                        <li><a class="{{ str_starts_with((string) $current, 'payments.') ? 'active' : '' }}" href="{{ route('payments.index') }}"><span class="menu-icon">PA</span><span>Paiements</span></a></li>
                    </ul>
                </div>
            </div>

            <div class="menu-section {{ $isAdministration ? 'is-open' : '' }}" data-menu-section>
                <button type="button" class="menu-section-toggle" data-menu-toggle>
                    <span class="menu-title">Administration</span>
                    <span class="menu-chevron">+</span>
                </button>
                <div class="menu-section-content">
                    <ul class="menu">
                        <li><a class="{{ str_starts_with((string) $current, 'users.') ? 'active' : '' }}" href="{{ route('users.index') }}"><span class="menu-icon">US</span><span>Utilisateurs</span></a></li>
                        <li><a class="{{ str_starts_with((string) $current, 'roles.') ? 'active' : '' }}" href="{{ route('roles.index') }}"><span class="menu-icon">RL</span><span>Roles utilisateurs</span></a></li>
                        <li><a class="{{ str_starts_with((string) $current, 'modules.') ? 'active' : '' }}" href="{{ route('modules.index') }}"><span class="menu-icon">MD</span><span>Modules</span></a></li>
                        <li><a class="{{ str_starts_with((string) $current, 'permissions.') ? 'active' : '' }}" href="{{ route('permissions.matrix') }}"><span class="menu-icon">MX</span><span>Matrice roles</span></a></li>
                    </ul>
                </div>
            </div>

            <div class="menu-section {{ $isServices ? 'is-open' : '' }}" data-menu-section>
                <button type="button" class="menu-section-toggle" data-menu-toggle>
                    <span class="menu-title">Services</span>
                    <span class="menu-chevron">+</span>
                </button>
                <div class="menu-section-content">
                    <div class="menu-subtitle">Services avec packs</div>
                    <ul class="menu">
                        @if (auth()->user()?->canFeature('troupe-musicale', 'list', 'view'))
                            <li><a class="{{ $currentModuleSlug === 'troupe-musicale' ? 'active' : '' }}" href="{{ route('service-modules.show', 'troupe-musicale') }}"><span class="menu-icon">TM</span><span>Troupe musicale</span></a></li>
                        @endif
                        @if (auth()->user()?->canFeature('photographe', 'list', 'view'))
                            <li><a class="{{ $currentModuleSlug === 'photographe' ? 'active' : '' }}" href="{{ route('service-modules.show', 'photographe') }}"><span class="menu-icon">PH</span><span>Photographe</span></a></li>
                        @endif
                    </ul>

                    <div class="menu-subtitle">Services standards</div>
                    <ul class="menu">
                        @if (auth()->user()?->canFeature('chanteur', 'list', 'view'))
                            <li><a class="{{ $currentModuleSlug === 'chanteur' ? 'active' : '' }}" href="{{ route('service-modules.show', 'chanteur') }}"><span class="menu-icon">CH</span><span>Chanteur</span></a></li>
                        @endif
                        @if (auth()->user()?->canFeature('notaire', 'list', 'view'))
                            <li><a class="{{ $currentModuleSlug === 'notaire' ? 'active' : '' }}" href="{{ route('service-modules.show', 'notaire') }}"><span class="menu-icon">NO</span><span>Notaire</span></a></li>
                        @endif
                        @if (auth()->user()?->canFeature('animation', 'list', 'view'))
                            <li><a class="{{ $currentModuleSlug === 'animation' ? 'active' : '' }}" href="{{ route('service-modules.show', 'animation') }}"><span class="menu-icon">AN</span><span>Animation</span></a></li>
                        @endif
                        @if (auth()->user()?->canFeature('voiture', 'list', 'view'))
                            <li><a class="{{ $currentModuleSlug === 'voiture' ? 'active' : '' }}" href="{{ route('service-modules.show', 'voiture') }}"><span class="menu-icon">VO</span><span>Voiture</span></a></li>
                        @endif
                    </ul>
                </div>
            </div>

        </aside>

        <main class="main">
            <div class="topbar">
                <button type="button" class="btn sidebar-toggle" data-sidebar-toggle>Menu</button>
                <div class="search">Workspace: QueenOnlineV2</div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <a href="{{ route('profile.password.edit') }}" class="btn">Mot de passe</a>
                    <div class="profile">Production panel • {{ now()->format('d/m/Y H:i') }}</div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn">Logout</button>
                    </form>
                </div>
            </div>

            @yield('content')
        </main>
    </div>
</body>
</html>
