@extends('layouts.app')

@section('content')
    @php
        $fullName = $staff->full_name !== '' ? $staff->full_name : ('Staff #' . $staff->id);
        $managerName = $staff->manager?->full_name ?: '-';
        $userRole = $staff->user?->role?->name ?? '-';
        $userLabel = $staff->user ? ($staff->user->name . ' - ' . $staff->user->email) : 'Aucun compte lie';
        $statusLabels = ['active' => 'Actif', 'suspended' => 'Suspendu', 'exited' => 'Sorti'];
        $maritalStatusLabel = $maritalStatusOptions[$staff->marital_status] ?? '-';
        $paymentMethodLabel = $paymentMethodOptions[$staff->payment_method] ?? '-';
        $documentsByType = $staff->documents->groupBy('document_type');
        $canManage = auth()->user()?->canFeature('staff', 'update', 'update') ?? false;
        $canViewSensitive = $canManage;
        $formatDate = fn ($value) => $value ? $value->format('d/m/Y') : '-';
        $formatMoney = fn ($value) => $value !== null ? number_format((float) $value, 3, ',', ' ') . ' TND' : '-';
    @endphp

    <style>
        .staff-profile { display: grid; gap: 16px; }
        .staff-hero { border: 1px solid #d5e3f2; border-radius: 20px; padding: 18px; background: linear-gradient(140deg, #f6fbff 0%, #edf7ff 100%); display: flex; gap: 18px; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .staff-hero-left { display: flex; gap: 18px; align-items: center; flex-wrap: wrap; }
        .staff-photo-lg { width: 118px; height: 118px; border-radius: 24px; object-fit: cover; background: #e2e8f0; border: 1px solid #cbd5e1; }
        .staff-photo-fallback { display: inline-flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 900; color: #173957; }
        .staff-title { margin: 0; font-size: 32px; color: #14314d; font-weight: 800; }
        .staff-sub { margin: 6px 0 0; color: #4e6a85; font-size: 14px; }
        .staff-chips { margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; }
        .staff-chip { border-radius: 999px; border: 1px solid #cfe0f2; padding: 6px 10px; font-size: 12px; font-weight: 700; color: #224a70; background: #f8fbff; }
        .staff-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .staff-card { border: 1px solid #dbe7f4; border-radius: 16px; background: #fff; overflow: hidden; }
        .staff-card.span-2 { grid-column: 1 / -1; }
        .staff-card-head { padding: 12px 14px; border-bottom: 1px solid #dbe7f4; background: #f8fbff; }
        .staff-card-title { margin: 0; color: #1e4f7b; font-size: 15px; text-transform: uppercase; letter-spacing: .04em; }
        .staff-card-note { margin: 4px 0 0; color: #6a8095; font-size: 12px; }
        .staff-card-body { padding: 14px; display: grid; gap: 10px; }
        .staff-kv-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .staff-kv { border: 1px solid #e5edf7; border-radius: 12px; padding: 10px; background: #fcfdff; display: grid; gap: 4px; }
        .staff-kv-key { color: #59728b; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; }
        .staff-kv-value { color: #1a3a57; font-size: 14px; font-weight: 600; line-height: 1.5; }
        .staff-sensitive-note { border: 1px dashed #f1c27d; border-radius: 12px; background: #fff9ef; color: #8b5a00; padding: 10px 12px; font-size: 13px; }
        .staff-doc-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .staff-doc-card { border: 1px solid #dde7f1; border-radius: 14px; background: #fbfdff; padding: 12px; display: grid; gap: 10px; }
        .staff-doc-item { display: flex; justify-content: space-between; gap: 10px; align-items: flex-start; padding: 10px; border: 1px solid #e7eef6; border-radius: 10px; background: #fff; }
        .staff-doc-meta { display: grid; gap: 3px; }
        .staff-doc-name { font-weight: 700; color: #173957; }
        .staff-doc-sub { font-size: 12px; color: #698096; }
        .staff-doc-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .staff-upload-form { display: grid; grid-template-columns: 1.2fr 1fr 1fr auto; gap: 10px; align-items: end; }
        @media (max-width: 980px) { .staff-grid, .staff-kv-grid, .staff-doc-grid, .staff-upload-form { grid-template-columns: 1fr; } }
    </style>

    <section class="staff-profile">
        <article class="staff-hero">
            <div class="staff-hero-left">
                @if ($staff->photo_path)
                    <img src="{{ asset('storage/' . $staff->photo_path) }}" alt="{{ $fullName }}" class="staff-photo-lg">
                @else
                    <span class="staff-photo-lg staff-photo-fallback">{{ strtoupper(substr((string) ($staff->first_name ?? ''), 0, 1) . substr((string) ($staff->last_name ?? ''), 0, 1)) ?: 'ST' }}</span>
                @endif
                <div>
                    <h1 class="staff-title">{{ $fullName }}</h1>
                    <p class="staff-sub">{{ $staff->position_title }} | {{ $staff->department?->name ?? 'Sans departement' }} | Responsable: {{ $managerName }}</p>
                    <p class="staff-sub">Matricule: {{ $staff->employee_code ?: '-' }} | Telephone: {{ $staff->phone ?: '-' }} | Email: {{ $staff->email ?: '-' }}</p>
                    <div class="staff-chips">
                        <span class="staff-chip">Statut: {{ $statusLabels[$staff->status] ?? ucfirst((string) $staff->status) }}</span>
                        <span class="staff-chip">Type poste: {{ $staff->employment_type === 'part-time' ? 'Temps partiel' : 'Permanent' }}</span>
                        <span class="staff-chip">Contrat: {{ $staff->contract_type }}</span>
                        <span class="staff-chip">Compte: {{ $staff->user ? 'Lie' : 'Aucun' }}</span>
                    </div>
                </div>
            </div>
            <div class="action-row"><a href="{{ route('staff.index') }}" class="btn">Retour staff</a></div>
        </article>

        <div class="staff-grid">
            <article class="staff-card"><div class="staff-card-head"><h3 class="staff-card-title">1. Informations personnelles</h3></div><div class="staff-card-body staff-kv-grid"><div class="staff-kv"><span class="staff-kv-key">Nom</span><span class="staff-kv-value">{{ $staff->last_name }}</span></div><div class="staff-kv"><span class="staff-kv-key">Prenom</span><span class="staff-kv-value">{{ $staff->first_name }}</span></div><div class="staff-kv"><span class="staff-kv-key">Date de naissance</span><span class="staff-kv-value">{{ $formatDate($staff->date_of_birth) }}</span></div><div class="staff-kv"><span class="staff-kv-key">Lieu de naissance</span><span class="staff-kv-value">{{ $staff->birth_place ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Nationalite</span><span class="staff-kv-value">{{ $staff->nationality ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">CIN / identite</span><span class="staff-kv-value">{{ $staff->cin ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Date delivrance CIN</span><span class="staff-kv-value">{{ $formatDate($staff->cin_issued_at) }}</span></div><div class="staff-kv"><span class="staff-kv-key">Telephone</span><span class="staff-kv-value">{{ $staff->phone ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Email</span><span class="staff-kv-value">{{ $staff->email ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Situation familiale</span><span class="staff-kv-value">{{ $maritalStatusLabel }}</span></div><div class="staff-kv"><span class="staff-kv-key">Enfants a charge</span><span class="staff-kv-value">{{ $staff->dependent_children_count ?? '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Gouvernorat / delegation</span><span class="staff-kv-value">{{ trim(($staff->governorate ?: '-') . ' / ' . ($staff->delegation ?: '-')) }}</span></div><div class="staff-kv" style="grid-column:1 / -1;"><span class="staff-kv-key">Adresse complete</span><span class="staff-kv-value">{{ $staff->address_line ?: '-' }}</span></div></div></article>
            <article class="staff-card"><div class="staff-card-head"><h3 class="staff-card-title">2. Contact d urgence</h3></div><div class="staff-card-body staff-kv-grid"><div class="staff-kv"><span class="staff-kv-key">Nom et prenom</span><span class="staff-kv-value">{{ $staff->emergency_contact_name ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Lien</span><span class="staff-kv-value">{{ $staff->emergency_contact_relationship ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Telephone</span><span class="staff-kv-value">{{ $staff->emergency_contact_phone ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Telephone secondaire</span><span class="staff-kv-value">{{ $staff->emergency_contact_phone_secondary ?: '-' }}</span></div></div></article>
            <article class="staff-card span-2"><div class="staff-card-head"><h3 class="staff-card-title">3. Informations professionnelles</h3></div><div class="staff-card-body staff-kv-grid"><div class="staff-kv"><span class="staff-kv-key">Matricule employe</span><span class="staff-kv-value">{{ $staff->employee_code ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Poste / fonction</span><span class="staff-kv-value">{{ $staff->position_title }}</span></div><div class="staff-kv"><span class="staff-kv-key">Departement</span><span class="staff-kv-value">{{ $staff->department?->name ?? '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Responsable hierarchique</span><span class="staff-kv-value">{{ $managerName }}</span></div><div class="staff-kv"><span class="staff-kv-key">Date d entree</span><span class="staff-kv-value">{{ $formatDate($staff->hire_date) }}</span></div><div class="staff-kv"><span class="staff-kv-key">Type de contrat</span><span class="staff-kv-value">{{ $staff->contract_type }}</span></div><div class="staff-kv"><span class="staff-kv-key">Date debut contrat</span><span class="staff-kv-value">{{ $formatDate($staff->contract_start_date) }}</span></div><div class="staff-kv"><span class="staff-kv-key">Date fin contrat</span><span class="staff-kv-value">{{ $formatDate($staff->contract_end_date) }}</span></div><div class="staff-kv"><span class="staff-kv-key">Periode d essai</span><span class="staff-kv-value">{{ $staff->probation_period ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Lieu de travail</span><span class="staff-kv-value">{{ $staff->work_location ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Horaires de travail</span><span class="staff-kv-value">{{ $staff->work_schedule ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Statut</span><span class="staff-kv-value">{{ $statusLabels[$staff->status] ?? ucfirst((string) $staff->status) }}</span></div><div class="staff-kv"><span class="staff-kv-key">Date de sortie</span><span class="staff-kv-value">{{ $formatDate($staff->exit_date) }}</span></div><div class="staff-kv" style="grid-column:1 / -1;"><span class="staff-kv-key">Motif de sortie</span><span class="staff-kv-value">{{ $staff->exit_reason ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Compte utilisateur lie</span><span class="staff-kv-value">{{ $userLabel }}</span></div><div class="staff-kv"><span class="staff-kv-key">Role du compte</span><span class="staff-kv-value">{{ $userRole }}</span></div></div></article>
            <article class="staff-card span-2"><div class="staff-card-head"><h3 class="staff-card-title">4. Informations paie</h3><p class="staff-card-note">Le RIB et les donnees salariales sont sensibles.</p></div><div class="staff-card-body">@if (! $canViewSensitive)<div class="staff-sensitive-note">Acces restreint. Les informations salariales et bancaires ne sont visibles que pour les personnes autorisees.</div>@else<div class="staff-kv-grid"><div class="staff-kv"><span class="staff-kv-key">Salaire de base</span><span class="staff-kv-value">{{ $formatMoney($staff->base_salary) }}</span></div><div class="staff-kv"><span class="staff-kv-key">Prime fixe</span><span class="staff-kv-value">{{ $formatMoney($staff->fixed_bonus) }}</span></div><div class="staff-kv"><span class="staff-kv-key">Primes variables</span><span class="staff-kv-value">{{ $formatMoney($staff->variable_bonus) }}</span></div><div class="staff-kv"><span class="staff-kv-key">Indemnites</span><span class="staff-kv-value">{{ $formatMoney($staff->allowances) }}</span></div><div class="staff-kv"><span class="staff-kv-key">Mode de paiement</span><span class="staff-kv-value">{{ $paymentMethodLabel }}</span></div><div class="staff-kv"><span class="staff-kv-key">Banque</span><span class="staff-kv-value">{{ $staff->bank_name ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">RIB</span><span class="staff-kv-value">{{ $staff->rib ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">CNSS</span><span class="staff-kv-value">{{ $staff->cnss_number ?: '-' }}</span></div><div class="staff-kv"><span class="staff-kv-key">Affiliation CNSS</span><span class="staff-kv-value">{{ $staff->cnss_affiliation_number ?: '-' }}</span></div><div class="staff-kv" style="grid-column:1 / -1;"><span class="staff-kv-key">Regime fiscal / paie</span><span class="staff-kv-value">{{ $staff->tax_information ?: '-' }}</span></div></div>@endif</div></article>
            <article class="staff-card span-2"><div class="staff-card-head"><h3 class="staff-card-title">5. Documents</h3><p class="staff-card-note">Joindre et classer les pieces RH dans le dossier staff.</p></div><div class="staff-card-body">@if ($canManage)<form method="POST" action="{{ route('staff.documents.store', $staff) }}" enctype="multipart/form-data" class="staff-upload-form">@csrf<div class="field"><label>Type de document</label><select class="search" style="max-width:none;" name="document_type" required>@foreach ($documentTypeOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div><div class="field"><label>Libelle</label><input class="search" style="max-width:none;" type="text" name="document_label" placeholder="Optionnel"></div><div class="field"><label>Fichier</label><input class="search" style="max-width:none;" type="file" name="document_file" accept=".pdf,.jpg,.jpeg,.png,.webp" required></div><div class="field"><button type="submit" class="btn btn-primary">Ajouter</button></div></form>@endif<div class="staff-doc-grid">@foreach ($documentTypeOptions as $type => $label)<div class="staff-doc-card"><div class="staff-kv-key" style="font-size:12px;">{{ $label }}</div>@forelse ($documentsByType->get($type, collect()) as $document)<div class="staff-doc-item"><div class="staff-doc-meta"><span class="staff-doc-name">{{ $document->document_label ?: $document->original_name }}</span><span class="staff-doc-sub">{{ $document->original_name }}</span><span class="staff-doc-sub">Ajoute le {{ optional($document->created_at)->format('d/m/Y H:i') }}{{ $document->uploader ? ' par ' . $document->uploader->name : '' }}</span></div><div class="staff-doc-actions"><a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" rel="noopener" class="btn">Ouvrir</a>@if ($canManage)<form method="POST" action="{{ route('staff.documents.destroy', [$staff, $document]) }}" onsubmit="return confirm('Supprimer ce document ?');">@csrf @method('DELETE')<button type="submit" class="btn">Supprimer</button></form>@endif</div></div>@empty<div class="staff-doc-sub">Aucun document dans cette categorie.</div>@endforelse</div>@endforeach</div></div></article>
        </div>
    </section>
@endsection
