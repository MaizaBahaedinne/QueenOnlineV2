<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? 'Dashboard') . ' - QueenPark Admin' }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

</head>
<body class="admin-body">
    @php
        $current = request()->route()?->getName();
        $currentModuleSlug = request()->route('module');
        $reservationService = (string) request()->query('service', '');
        $currentUser = auth()->user();
        $canViewClients = $currentUser?->canFeature('clients', 'list', 'view') ?? false;
        $canViewStaff = $currentUser?->canFeature('staff', 'list', 'view') ?? false;
        $canViewPayments = $currentUser?->canFeature('payments', 'list', 'view') ?? false;
        $canViewReservations = $currentUser?->canFeature('reservations', 'list', 'view') ?? false;
        $canViewUsers = $currentUser?->canFeature('users', 'list', 'view') ?? false;
        $canViewRoles = $currentUser?->canFeature('roles', 'list', 'view') ?? false;
        $canViewModules = $currentUser?->canFeature('modules', 'list', 'view') ?? false;
        $canViewPermissions = $currentUser?->canFeature('permissions', 'list', 'view') ?? false;
        $canViewMigrationMappings = $currentUser?->canFeature('migration-mappings', 'list', 'view') ?? false;
        $canViewSalles = $currentUser?->canFeature('salles', 'list', 'view') ?? false;
        $canViewTroupe = $currentUser?->canFeature('troupe-musicale', 'list', 'view') ?? false;
        $canViewPhotographe = $currentUser?->canFeature('photographe', 'list', 'view') ?? false;
        $canViewChanteur = $currentUser?->canFeature('chanteur', 'list', 'view') ?? false;
        $canViewNotaire = $currentUser?->canFeature('notaire', 'list', 'view') ?? false;
        $canViewAnimation = $currentUser?->canFeature('animation', 'list', 'view') ?? false;
        $canViewVoiture = $currentUser?->canFeature('voiture', 'list', 'view') ?? false;
        $reservationServicePermissions = [
            'salles' => $canViewSalles,
            'troupe-musicale' => $canViewTroupe,
            'photographe' => $canViewPhotographe,
            'chanteur' => $canViewChanteur,
            'notaire' => $canViewNotaire,
            'animation' => $canViewAnimation,
            'voiture' => $canViewVoiture,
        ];
        $reservationServiceLabels = [
            'salles' => 'Salles',
            'troupe-musicale' => 'Troupe musicale',
            'photographe' => 'Photographe',
            'chanteur' => 'Chanteur',
            'notaire' => 'Notaire',
            'animation' => 'Animation',
            'voiture' => 'Voiture',
        ];
        $visibleReservationServices = collect($reservationServicePermissions)
            ->filter(fn (bool $canView) => $canView)
            ->keys()
            ->values()
            ->all();
        $isPilotage = $current === 'dashboard';
        $isExploitation = str_starts_with((string) $current, 'clients.')
            || str_starts_with((string) $current, 'staff.')
            || str_starts_with((string) $current, 'reservations.')
            || str_starts_with((string) $current, 'payments.');
        $isAdministration = str_starts_with((string) $current, 'users.')
            || str_starts_with((string) $current, 'roles.')
            || str_starts_with((string) $current, 'modules.')
            || str_starts_with((string) $current, 'migration-mappings.')
            || str_starts_with((string) $current, 'permissions.');
        $isServices = str_starts_with((string) $current, 'salles.')
            || in_array((string) $currentModuleSlug, ['troupe-musicale', 'photographe', 'chanteur', 'notaire', 'animation', 'voiture'], true);
    @endphp

    <div class="app-shell">
        @if (session()->has('impersonator_user_id'))
            <div style="position:fixed;top:12px;left:50%;transform:translateX(-50%);z-index:9999;background:#fff4d6;border:1px solid #f2d28a;color:#7a5310;padding:10px 14px;border-radius:999px;box-shadow:0 10px 24px rgba(0,0,0,.12);display:flex;align-items:center;gap:10px;flex-wrap:wrap;max-width:min(94vw,760px);">
                <strong>Mode impersonation actif</strong>
                <span>Tu consultes l application en tant que {{ auth()->user()?->name ?? 'utilisateur' }}.</span>
                <form method="POST" action="{{ route('impersonation.stop') }}">
                    @csrf
                    <button type="submit" class="btn" style="background:#7a5310;color:#fff;border-color:#7a5310;">Retour a mon compte</button>
                </form>
            </div>
        @endif

        <aside class="sidebar">
            <div class="brand">
                <div class="brand-badge">Q</div>
                <div class="brand-text">QueenPark Admin</div>
            </div>

            <div class="menu-section {{ $isPilotage ? 'is-open' : '' }}" data-menu-section>
                <button type="button" class="menu-section-toggle" data-menu-toggle aria-expanded="{{ $isPilotage ? 'true' : 'false' }}">
                    <span class="menu-title">Pilotage</span>
                    <i class="fa fa-angle-down menu-chevron" aria-hidden="true"></i>
                </button>
                <div class="menu-section-content">
                    <ul class="menu">
                        <li><a class="{{ $current === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="fa fa-tachometer menu-icon" aria-hidden="true"></i><span>Dashboard</span></a></li>
                    </ul>
                </div>
            </div>

            <div class="menu-section {{ $isExploitation ? 'is-open' : '' }}" data-menu-section>
                <button type="button" class="menu-section-toggle" data-menu-toggle aria-expanded="{{ $isExploitation ? 'true' : 'false' }}">
                    <span class="menu-title">Exploitation</span>
                    <i class="fa fa-angle-down menu-chevron" aria-hidden="true"></i>
                </button>
                <div class="menu-section-content">
                    <ul class="menu">
                        @if ($canViewClients)
                            <li><a class="{{ str_starts_with((string) $current, 'clients.') ? 'active' : '' }}" href="{{ route('clients.index') }}"><i class="fa fa-users menu-icon" aria-hidden="true"></i><span>Clients</span></a></li>
                        @endif
                        @if ($canViewStaff)
                            <li><a class="{{ str_starts_with((string) $current, 'staff.') ? 'active' : '' }}" href="{{ route('staff.index') }}"><i class="fa fa-id-badge menu-icon" aria-hidden="true"></i><span>Ressource Humaine</span></a></li>
                        @endif
                        @if ($canViewPayments)
                            <li><a class="{{ str_starts_with((string) $current, 'payments.') ? 'active' : '' }}" href="{{ route('payments.index') }}"><i class="fa fa-credit-card menu-icon" aria-hidden="true"></i><span>Paiements</span></a></li>
                        @endif
                        @if ($canViewReservations)
                            <li>
                                <a class="{{ str_starts_with((string) $current, 'reservations.') ? 'active' : '' }}" href="{{ route('reservations.index') }}"><i class="fa fa-calendar menu-icon" aria-hidden="true"></i><span>Reservations</span></a>
                                @if (str_starts_with((string) $current, 'reservations.'))
                                    <ul class="menu-submenu">
                                        @if (count($visibleReservationServices) > 1)
                                            <li><a class="{{ $reservationService === 'all' ? 'active' : '' }}" href="{{ route('reservations.index', ['service' => 'all']) }}">Toutes</a></li>
                                        @endif
                                        @foreach ($visibleReservationServices as $serviceSlug)
                                            <li><a class="{{ $reservationService === $serviceSlug || ($reservationService === '' && $serviceSlug === 'salles') ? 'active' : '' }}" href="{{ route('reservations.index', ['service' => $serviceSlug]) }}">{{ $reservationServiceLabels[$serviceSlug] ?? ucfirst(str_replace('-', ' ', $serviceSlug)) }}</a></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="menu-section {{ $isAdministration ? 'is-open' : '' }}" data-menu-section>
                <button type="button" class="menu-section-toggle" data-menu-toggle aria-expanded="{{ $isAdministration ? 'true' : 'false' }}">
                    <span class="menu-title">Administration</span>
                    <i class="fa fa-angle-down menu-chevron" aria-hidden="true"></i>
                </button>
                <div class="menu-section-content">
                    <ul class="menu">
                        @if ($canViewUsers)
                            <li><a class="{{ str_starts_with((string) $current, 'users.') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="fa fa-user menu-icon" aria-hidden="true"></i><span>Utilisateurs</span></a></li>
                        @endif
                        @if ($canViewRoles)
                            <li><a class="{{ str_starts_with((string) $current, 'roles.') ? 'active' : '' }}" href="{{ route('roles.index') }}"><i class="fa fa-shield menu-icon" aria-hidden="true"></i><span>Roles utilisateurs</span></a></li>
                        @endif
                        @if ($canViewModules)
                            <li><a class="{{ str_starts_with((string) $current, 'modules.') ? 'active' : '' }}" href="{{ route('modules.index') }}"><i class="fa fa-th-large menu-icon" aria-hidden="true"></i><span>Modules</span></a></li>
                        @endif
                        @if ($canViewMigrationMappings)
                            <li><a class="{{ str_starts_with((string) $current, 'migration-mappings.') ? 'active' : '' }}" href="{{ route('migration-mappings.index') }}"><i class="fa fa-random menu-icon" aria-hidden="true"></i><span>Mapping migration</span></a></li>
                        @endif
                        @if ($canViewPermissions)
                            <li><a class="{{ str_starts_with((string) $current, 'permissions.') ? 'active' : '' }}" href="{{ route('permissions.matrix') }}"><i class="fa fa-lock menu-icon" aria-hidden="true"></i><span>Matrice roles</span></a></li>
                        @endif
                    </ul>
                </div>
            </div>

            <div class="menu-section {{ $isServices ? 'is-open' : '' }}" data-menu-section>
                <button type="button" class="menu-section-toggle" data-menu-toggle aria-expanded="{{ $isServices ? 'true' : 'false' }}">
                    <span class="menu-title">Services</span>
                    <i class="fa fa-angle-down menu-chevron" aria-hidden="true"></i>
                </button>
                <div class="menu-section-content">
                    <div class="menu-subtitle">Services avec packs</div>
                    <ul class="menu">
                        @if ($canViewTroupe)
                            <li><a class="{{ $currentModuleSlug === 'troupe-musicale' ? 'active' : '' }}" href="{{ route('service-modules.show', 'troupe-musicale') }}"><i class="fa fa-music menu-icon" aria-hidden="true"></i><span>Troupe musicale</span></a></li>
                        @endif
                        @if ($canViewPhotographe)
                            <li><a class="{{ $currentModuleSlug === 'photographe' ? 'active' : '' }}" href="{{ route('service-modules.show', 'photographe') }}"><i class="fa fa-camera menu-icon" aria-hidden="true"></i><span>Photographe</span></a></li>
                        @endif
                    </ul>

                    <div class="menu-subtitle">Services standards</div>
                    <ul class="menu">
                        @if ($canViewSalles)
                            <li><a class="{{ str_starts_with((string) $current, 'salles.') ? 'active' : '' }}" href="{{ route('salles.index') }}"><i class="fa fa-building menu-icon" aria-hidden="true"></i><span>Salles</span></a></li>
                        @endif
                        @if ($canViewChanteur)
                            <li><a class="{{ $currentModuleSlug === 'chanteur' ? 'active' : '' }}" href="{{ route('service-modules.show', 'chanteur') }}"><i class="fa fa-microphone menu-icon" aria-hidden="true"></i><span>Chanteur</span></a></li>
                        @endif
                        @if ($canViewNotaire)
                            <li><a class="{{ $currentModuleSlug === 'notaire' ? 'active' : '' }}" href="{{ route('service-modules.show', 'notaire') }}"><i class="fa fa-file-text-o menu-icon" aria-hidden="true"></i><span>Notaire</span></a></li>
                        @endif
                        @if ($canViewAnimation)
                            <li><a class="{{ $currentModuleSlug === 'animation' ? 'active' : '' }}" href="{{ route('service-modules.show', 'animation') }}"><i class="fa fa-smile-o menu-icon" aria-hidden="true"></i><span>Animation</span></a></li>
                        @endif
                        @if ($canViewVoiture)
                            <li><a class="{{ $currentModuleSlug === 'voiture' ? 'active' : '' }}" href="{{ route('service-modules.show', 'voiture') }}"><i class="fa fa-car menu-icon" aria-hidden="true"></i><span>Voiture</span></a></li>
                        @endif
                    </ul>
                </div>
            </div>

        </aside>

        <main class="main">
            <div class="topbar">
                <button type="button" class="btn sidebar-toggle" data-sidebar-toggle>Menu</button>
                <div class="search">Workspace: QueenOnlineV2</div>
                <div class="topbar-right">
                    <button type="button" class="top-icon-btn" aria-label="Messages">
                        <i class="fa fa-envelope" aria-hidden="true"></i>
                        <span class="top-icon-badge" aria-hidden="true"></span>
                    </button>
                    <button type="button" class="top-icon-btn" aria-label="Notifications">
                        <i class="fa fa-bell" aria-hidden="true"></i>
                        <span class="top-icon-badge" aria-hidden="true"></span>
                    </button>
                    <div class="user-menu" data-user-menu>
                        <button type="button" class="user-chip user-chip-button" data-user-menu-toggle aria-expanded="false" aria-haspopup="true">
                            <i class="fa fa-user-circle" aria-hidden="true"></i>
                            <span>{{ auth()->user()?->name ?? 'Utilisateur' }}</span>
                            <i class="fa fa-angle-down" aria-hidden="true"></i>
                        </button>

                        <div class="user-menu-dropdown" data-user-menu-dropdown>
                            <div class="user-menu-header">
                                <strong>{{ auth()->user()?->name ?? 'Utilisateur' }}</strong>
                                <span>Production • {{ now()->format('d/m/Y H:i') }}</span>
                            </div>
                            <a href="{{ route('profile.password.edit') }}" class="user-menu-item">
                                <i class="fa fa-key" aria-hidden="true"></i>
                                <span>Mot de passe</span>
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="user-menu-item user-menu-item-danger">
                                    <i class="fa fa-sign-out" aria-hidden="true"></i>
                                    <span>Deconnexion</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            @yield('content')
        </main>
    </div>
</body>
</html>
