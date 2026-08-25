@extends('layouts.app')

@section('content')
    @php
        $fullName = $staff->full_name !== '' ? $staff->full_name : ('Staff #' . $staff->id);
        $managerName = trim((string) (($staff->manager?->first_name ?? '') . ' ' . ($staff->manager?->last_name ?? '')));
        $userRole = $staff->user?->role?->name ?? '-';
        $userLabel = $staff->user ? ($staff->user->name . ' - ' . $staff->user->email) : 'Aucun compte lie';
    @endphp

    <style>
        .staff-show {
            display: grid;
            gap: 14px;
        }

        .staff-hero {
            border: 1px solid #d5e3f2;
            border-radius: 16px;
            padding: 16px;
            background: linear-gradient(140deg, #f6fbff 0%, #eef6ff 100%);
            display: flex;
            gap: 16px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .staff-hero-left {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .staff-photo-lg {
            width: 110px;
            height: 110px;
            border-radius: 20px;
            object-fit: cover;
            background: #e2e8f0;
            border: 1px solid #cbd5e1;
        }

        .staff-photo-fallback {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 800;
            color: #173957;
        }

        .staff-title {
            margin: 0;
            font-size: 30px;
            color: #14314d;
            font-weight: 800;
        }

        .staff-sub {
            margin: 6px 0 0;
            color: #4e6a85;
            font-size: 14px;
        }

        .staff-chips {
            margin-top: 10px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .staff-chip {
            border-radius: 999px;
            border: 1px solid #cfe0f2;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
            color: #224a70;
            background: #f8fbff;
        }

        .staff-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .staff-card {
            border: 1px solid #dbe7f4;
            border-radius: 14px;
            background: #fff;
            overflow: hidden;
        }

        .staff-card-head {
            padding: 12px 14px;
            border-bottom: 1px solid #dbe7f4;
            background: #f8fbff;
        }

        .staff-card-title {
            margin: 0;
            color: #1e4f7b;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .staff-card-body {
            padding: 12px;
            display: grid;
            gap: 8px;
        }

        .staff-kv {
            border: 1px solid #e5edf7;
            border-radius: 10px;
            padding: 9px 10px;
            background: #fcfdff;
            display: grid;
            gap: 3px;
        }

        .staff-kv-key {
            color: #59728b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-weight: 700;
        }

        .staff-kv-value {
            color: #1a3a57;
            font-size: 14px;
            font-weight: 600;
        }

        @media (max-width: 960px) {
            .staff-grid { grid-template-columns: 1fr; }
        }
    </style>

    <section class="staff-show">
        <article class="staff-hero">
            <div class="staff-hero-left">
                @if ($staff->photo_path)
                    <img src="{{ asset('storage/' . $staff->photo_path) }}" alt="{{ $fullName }}" class="staff-photo-lg">
                @else
                    <span class="staff-photo-lg staff-photo-fallback">{{ strtoupper(substr((string) ($staff->first_name ?? ''), 0, 1) . substr((string) ($staff->last_name ?? ''), 0, 1)) ?: 'ST' }}</span>
                @endif

                <div>
                    <h1 class="staff-title">{{ $fullName }}</h1>
                    <p class="staff-sub">Poste: {{ $staff->position_title }} | Departement: {{ $staff->department?->name ?? '-' }} | Manager: {{ $managerName !== '' ? $managerName : '-' }}</p>
                    <div class="staff-chips">
                        <span class="staff-chip">{{ $staff->employment_type === 'part-time' ? 'Temps partiel' : 'Permanent' }}</span>
                        <span class="staff-chip">{{ $staff->contract_type }}</span>
                        <span class="staff-chip">Statut: {{ $staff->status ?? '-' }}</span>
                    </div>
                </div>
            </div>

            <div>
                <a href="{{ route('staff.index') }}" class="btn">Retour staff</a>
            </div>
        </article>

        <div class="staff-grid">
            <article class="staff-card">
                <div class="staff-card-head">
                    <h3 class="staff-card-title">Informations personnelles</h3>
                </div>
                <div class="staff-card-body">
                    <div class="staff-kv"><span class="staff-kv-key">Nom</span><span class="staff-kv-value">{{ $staff->last_name }}</span></div>
                    <div class="staff-kv"><span class="staff-kv-key">Prenom</span><span class="staff-kv-value">{{ $staff->first_name }}</span></div>
                    <div class="staff-kv"><span class="staff-kv-key">CIN</span><span class="staff-kv-value">{{ $staff->cin ?: '-' }}</span></div>
                    <div class="staff-kv"><span class="staff-kv-key">Date d embauche</span><span class="staff-kv-value">{{ $staff->hire_date ? $staff->hire_date->format('d/m/Y') : '-' }}</span></div>
                </div>
            </article>

            <article class="staff-card">
                <div class="staff-card-head">
                    <h3 class="staff-card-title">Cadre professionnel</h3>
                </div>
                <div class="staff-card-body">
                    <div class="staff-kv"><span class="staff-kv-key">Poste</span><span class="staff-kv-value">{{ $staff->position_title }}</span></div>
                    <div class="staff-kv"><span class="staff-kv-key">Departement</span><span class="staff-kv-value">{{ $staff->department?->name ?? '-' }}</span></div>
                    <div class="staff-kv"><span class="staff-kv-key">Type de poste</span><span class="staff-kv-value">{{ $staff->employment_type === 'part-time' ? 'Temps partiel' : 'Permanent' }}</span></div>
                    <div class="staff-kv"><span class="staff-kv-key">Contrat</span><span class="staff-kv-value">{{ $staff->contract_type }}</span></div>
                    <div class="staff-kv"><span class="staff-kv-key">Manager</span><span class="staff-kv-value">{{ $managerName !== '' ? $managerName : '-' }}</span></div>
                </div>
            </article>

            <article class="staff-card" style="grid-column: 1 / -1;">
                <div class="staff-card-head">
                    <h3 class="staff-card-title">Compte utilisateur lie</h3>
                </div>
                <div class="staff-card-body">
                    <div class="staff-kv"><span class="staff-kv-key">Compte</span><span class="staff-kv-value">{{ $userLabel }}</span></div>
                    <div class="staff-kv"><span class="staff-kv-key">Role</span><span class="staff-kv-value">{{ $userRole }}</span></div>
                </div>
            </article>
        </div>
    </section>
@endsection
