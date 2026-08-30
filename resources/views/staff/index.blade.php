@extends('layouts.app')

@section('content')
    @php
        $canCreate = auth()->user()?->canFeature('staff', 'create', 'create') ?? false;
        $canUpdate = auth()->user()?->canFeature('staff', 'update', 'update') ?? false;
        $canDelete = auth()->user()?->canFeature('staff', 'delete', 'delete') ?? false;
        $activeCount = $staffMembers->where('status', 'active')->count();
        $suspendedCount = $staffMembers->where('status', 'suspended')->count();
        $exitedCount = $staffMembers->where('status', 'exited')->count();
        $statusLabels = ['active' => 'Actif', 'suspended' => 'Suspendu', 'exited' => 'Sorti'];
    @endphp

    <style>
        .staff-page { display: grid; gap: 16px; }
        .staff-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .staff-stat { border: 1px solid #d7e3ef; border-radius: 16px; padding: 14px; background: linear-gradient(140deg, #f7fbff 0%, #eef5fb 100%); }
        .staff-stat-label { font-size: 12px; color: #5d7388; text-transform: uppercase; letter-spacing: .06em; font-weight: 700; }
        .staff-stat-value { margin-top: 6px; font-size: 28px; color: #15324b; font-weight: 800; }
        .staff-stat-note { margin-top: 4px; font-size: 13px; color: #607a92; }
        .staff-toolbar { display: flex; justify-content: space-between; gap: 12px; align-items: center; flex-wrap: wrap; }
        .staff-table-wrap { overflow-x: auto; }
        .staff-name-cell { display: grid; gap: 3px; }
        .staff-name-main { font-weight: 700; color: #16344f; }
        .staff-name-sub { font-size: 12px; color: #667f95; }
        .staff-avatar { width: 46px; height: 46px; border-radius: 14px; object-fit: cover; background: #e2e8f0; border: 1px solid #cbd5e1; }
        .staff-avatar-fallback { display: inline-flex; align-items: center; justify-content: center; font-weight: 800; color: #1d4366; }
        .staff-status-chip { display: inline-flex; align-items: center; border-radius: 999px; padding: 6px 10px; font-size: 12px; font-weight: 700; }
        .staff-status-active { background: #e8fff1; color: #0d6b3d; }
        .staff-status-suspended { background: #fff7e6; color: #9a6500; }
        .staff-status-exited { background: #fff0f0; color: #a23131; }
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 19, 26, 0.56); display: none; align-items: center; justify-content: center; padding: 18px; z-index: 80; }
        .modal-overlay.show { display: flex; }
        .modal-card { width: min(1120px, 100%); max-height: 92vh; overflow: auto; background: #fff; border: 1px solid var(--line); border-radius: 18px; padding: 18px; box-shadow: var(--shadow); }
        .modal-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
        .modal-title { margin: 0; font-size: 20px; color: #13324d; }
        .modal-subtitle { margin: 4px 0 0; color: #678197; font-size: 13px; }
        .action-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .staff-form { display: grid; gap: 14px; }
        .staff-section { border: 1px solid #dbe6f0; border-radius: 16px; overflow: hidden; background: #fff; }
        .staff-section-head { padding: 12px 14px; background: #f8fbff; border-bottom: 1px solid #dbe6f0; }
        .staff-section-title { margin: 0; color: #1b4b75; font-size: 15px; font-weight: 800; }
        .staff-section-note { margin: 4px 0 0; font-size: 12px; color: #6a8095; }
        .staff-section-body { padding: 14px; display: grid; gap: 12px; }
        .form-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .form-grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .field { display: grid; gap: 6px; }
        .field.full-span { grid-column: 1 / -1; }
        .field label { font-size: 12px; font-weight: 700; color: #51687e; text-transform: uppercase; letter-spacing: .04em; }
        .field textarea.search { min-height: 92px; resize: vertical; }
        .staff-photo-preview { width: 96px; height: 96px; border-radius: 18px; object-fit: cover; background: #e2e8f0; border: 1px solid #cbd5e1; display: block; }
        .crop-modal-card { width: min(920px, 100%); }
        .crop-layout { display: grid; grid-template-columns: minmax(280px, 460px) minmax(220px, 1fr); gap: 18px; align-items: start; }
        .crop-stage { position: relative; width: min(460px, 100%); aspect-ratio: 1 / 1; overflow: hidden; border-radius: 20px; background: linear-gradient(135deg, #dce8f4 0%, #eef4fa 100%); touch-action: none; }
        .crop-stage::after { content: ''; position: absolute; inset: 0; border: 2px solid rgba(255,255,255,.9); border-radius: 20px; box-shadow: 0 0 0 9999px rgba(15, 23, 42, .34) inset; pointer-events: none; }
        .crop-image { position: absolute; top: 0; left: 0; transform-origin: top left; cursor: grab; user-select: none; -webkit-user-drag: none; max-width: none; }
        .crop-image.dragging { cursor: grabbing; }
        .crop-sidebar { display: grid; gap: 14px; }
        .crop-help { margin: 0; color: #5b7187; font-size: 13px; line-height: 1.5; }
        .crop-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .crop-slider { width: 100%; }
        .conditional-hidden { display: none; }
        @media (max-width: 980px) { .staff-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } .form-grid-3 { grid-template-columns: 1fr 1fr; } }
        @media (max-width: 860px) { .crop-layout { grid-template-columns: 1fr; } .crop-stage { width: 100%; } }
        @media (max-width: 780px) { .form-grid-2, .form-grid-3, .staff-summary { grid-template-columns: 1fr; } }
    </style>

    <section class="staff-page panel">
        <div class="staff-toolbar">
            <div>
                <h1 class="panel-title">Fiches Ressource Humaine</h1>
                <p class="panel-sub">Dossiers RH complets, comptes lies et photo recadree au format carre.</p>
            </div>
            @if ($canCreate)
                <button type="button" class="btn btn-primary" data-open-modal="staff-create-modal">Nouvelle fiche Ressource Humaine</button>
            @endif
        </div>

        @if (session('success'))
            <p class="badge badge-success">{{ session('success') }}</p>
        @endif

        @if ($errors->any())
            <div class="badge" style="background:#fff1f0;color:#9c2f2a;border-color:#f1c6c2;text-align:left;">
                <strong>Verifie les informations:</strong>
                <ul style="margin:6px 0 0 18px;padding:0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="staff-summary">
            <article class="staff-stat"><div class="staff-stat-label">Total ressources humaines</div><div class="staff-stat-value">{{ $staffMembers->count() }}</div><div class="staff-stat-note">Membres enregistres</div></article>
            <article class="staff-stat"><div class="staff-stat-label">Actifs</div><div class="staff-stat-value">{{ $activeCount }}</div><div class="staff-stat-note">En poste actuellement</div></article>
            <article class="staff-stat"><div class="staff-stat-label">Suspendus</div><div class="staff-stat-value">{{ $suspendedCount }}</div><div class="staff-stat-note">Temporairement inactifs</div></article>
            <article class="staff-stat"><div class="staff-stat-label">Sortis</div><div class="staff-stat-value">{{ $exitedCount }}</div><div class="staff-stat-note">Historique des sorties</div></article>
        </div>

        <div class="staff-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Matricule</th>
                        <th>Nom complet</th>
                        <th>Poste</th>
                        <th>Departement</th>
                        <th>Telephone</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staffMembers as $member)
                        @php
                            $initials = strtoupper(substr((string) ($member->first_name ?? ''), 0, 1) . substr((string) ($member->last_name ?? ''), 0, 1));
                            $statusClass = match($member->status) {
                                'suspended' => 'staff-status-suspended',
                                'exited' => 'staff-status-exited',
                                default => 'staff-status-active',
                            };
                            $staffPayload = [
                                'first_name' => $member->first_name,
                                'last_name' => $member->last_name,
                                'date_of_birth' => optional($member->date_of_birth)->format('Y-m-d'),
                                'birth_place' => $member->birth_place,
                                'nationality' => $member->nationality,
                                'cin' => $member->cin,
                                'cin_issued_at' => optional($member->cin_issued_at)->format('Y-m-d'),
                                'address_line' => $member->address_line,
                                'governorate' => $member->governorate,
                                'delegation' => $member->delegation,
                                'phone' => $member->phone,
                                'email' => $member->email,
                                'marital_status' => $member->marital_status,
                                'dependent_children_count' => $member->dependent_children_count,
                                'emergency_contact_name' => $member->emergency_contact_name,
                                'emergency_contact_relationship' => $member->emergency_contact_relationship,
                                'emergency_contact_phone' => $member->emergency_contact_phone,
                                'emergency_contact_phone_secondary' => $member->emergency_contact_phone_secondary,
                                'employee_code' => $member->employee_code,
                                'hire_date' => optional($member->hire_date)->format('Y-m-d'),
                                'position_title' => $member->position_title,
                                'department_id' => $member->department_id,
                                'employment_type' => $member->employment_type,
                                'contract_type' => $member->contract_type,
                                'contract_start_date' => optional($member->contract_start_date)->format('Y-m-d'),
                                'contract_end_date' => optional($member->contract_end_date)->format('Y-m-d'),
                                'probation_period' => $member->probation_period,
                                'work_location' => $member->work_location,
                                'work_schedule' => $member->work_schedule,
                                'manager_id' => $member->manager_id,
                                'user_id' => $member->user_id,
                                'status' => $member->status,
                                'exit_date' => optional($member->exit_date)->format('Y-m-d'),
                                'exit_reason' => $member->exit_reason,
                                'base_salary' => $member->base_salary,
                                'fixed_bonus' => $member->fixed_bonus,
                                'variable_bonus' => $member->variable_bonus,
                                'allowances' => $member->allowances,
                                'payment_method' => $member->payment_method,
                                'bank_name' => $member->bank_name,
                                'rib' => $member->rib,
                                'cnss_number' => $member->cnss_number,
                                'cnss_affiliation_number' => $member->cnss_affiliation_number,
                                'tax_information' => $member->tax_information,
                            ];
                        @endphp
                        <tr>
                            <td>
                                @if ($member->photo_path)
                                    <img src="{{ asset('storage/' . $member->photo_path) }}" alt="{{ $member->full_name }}" class="staff-avatar">
                                @else
                                    <span class="staff-avatar staff-avatar-fallback">{{ $initials !== '' ? $initials : 'ST' }}</span>
                                @endif
                            </td>
                            <td>{{ $member->employee_code ?: '-' }}</td>
                            <td><div class="staff-name-cell"><span class="staff-name-main">{{ $member->full_name }}</span><span class="staff-name-sub">{{ $member->email ?: ($member->cin ?: 'Aucune identification complementaire') }}</span></div></td>
                            <td>{{ $member->position_title }}</td>
                            <td>{{ $member->department?->name ?? '-' }}</td>
                            <td>{{ $member->phone ?: '-' }}</td>
                            <td><span class="staff-status-chip {{ $statusClass }}">{{ $statusLabels[$member->status] ?? ucfirst((string) $member->status) }}</span></td>
                            <td>
                                <div class="action-row">
                                    <a href="{{ route('staff.show', $member) }}" class="btn">Fiche</a>
                                    @if ($canUpdate)
                                        <button type="button" class="btn" data-open-modal="staff-edit-modal" data-staff-id="{{ $member->id }}" data-staff='@json($staffPayload)' data-staff-photo-url="{{ $member->photo_path ? asset('storage/' . $member->photo_path) : '' }}">Modifier</button>
                                    @endif
                                    @if ($canDelete)
                                        <button type="button" class="btn" data-open-modal="staff-delete-modal" data-staff-id="{{ $member->id }}" data-staff-name="{{ $member->full_name }}">Supprimer</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="muted">Aucune ressource humaine.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canCreate)
        <div class="modal-overlay" id="staff-create-modal"><div class="modal-card"><div class="modal-head"><div><h3 class="modal-title">Nouvelle fiche Ressource Humaine</h3><p class="modal-subtitle">Creation d un dossier RH complet avec informations personnelles, professionnelles et paie.</p></div><button type="button" class="btn" data-close-modal>Fermer</button></div><form method="POST" action="{{ route('staff.store') }}" enctype="multipart/form-data">@csrf @include('staff._form', ['prefix' => 'create']) <div class="action-row" style="margin-top:14px; justify-content:flex-end;"><button type="submit" class="btn btn-primary">Enregistrer la fiche</button></div></form></div></div>
    @endif

    @if ($canUpdate)
        <div class="modal-overlay" id="staff-edit-modal"><div class="modal-card"><div class="modal-head"><div><h3 class="modal-title">Modifier la fiche Ressource Humaine</h3><p class="modal-subtitle">Mise a jour des donnees RH, paie, statut et rattachement systeme.</p></div><button type="button" class="btn" data-close-modal>Fermer</button></div><form method="POST" id="staff-edit-form" action="#" enctype="multipart/form-data">@csrf @method('PATCH') @include('staff._form', ['prefix' => 'edit']) <div class="action-row" style="margin-top:14px; justify-content:flex-end;"><button type="submit" class="btn btn-primary">Mettre a jour la fiche</button></div></form></div></div>
    @endif

    @if ($canDelete)
        <div class="modal-overlay" id="staff-delete-modal"><div class="modal-card" style="width:min(520px, 100%);"><div class="modal-head"><h3 class="modal-title">Supprimer la fiche Ressource Humaine</h3><button type="button" class="btn" data-close-modal>Fermer</button></div><p id="staff-delete-text" class="panel-sub"></p><form method="POST" id="staff-delete-form" action="#" style="margin-top:10px;">@csrf @method('DELETE')<button type="submit" class="btn">Confirmer suppression</button></form></div></div>
    @endif

    <div class="modal-overlay" id="staff-photo-crop-modal"><div class="modal-card crop-modal-card"><div class="modal-head"><div><h3 class="modal-title">Recadrer la photo</h3><p class="modal-subtitle">Format final carre 1:1 pour le profil Ressource Humaine.</p></div><button type="button" class="btn" id="staff-photo-crop-cancel-top">Fermer</button></div><div class="crop-layout"><div class="crop-stage" id="staff-photo-crop-stage"><img src="" alt="Recadrage photo Ressource Humaine" id="staff-photo-crop-image" class="crop-image" style="display:none;"></div><div class="crop-sidebar"><p class="crop-help">Glisse l image pour la positionner dans le cadre, puis ajuste le zoom. La photo enregistree sera recadree en carre 1:1.</p><div><label for="staff-photo-crop-zoom" style="display:block; margin-bottom:8px; font-weight:600;">Zoom</label><input type="range" id="staff-photo-crop-zoom" class="crop-slider" min="100" max="400" step="1" value="100"></div><div class="crop-actions"><button type="button" class="btn" id="staff-photo-crop-reset">Reinitialiser</button><button type="button" class="btn" id="staff-photo-crop-cancel">Annuler</button><button type="button" class="btn btn-primary" id="staff-photo-crop-apply">Utiliser cette photo</button></div></div></div></div></div>

    <script>
        const openModalButtons = document.querySelectorAll('[data-open-modal]');
        const closeModalButtons = document.querySelectorAll('[data-close-modal]');
        const openModal = (id) => { const modal = document.getElementById(id); if (modal) modal.classList.add('show'); };
        const closeModal = (modal) => modal.classList.remove('show');
        const editFieldMap = { first_name: 'staff-edit-first-name', last_name: 'staff-edit-last-name', date_of_birth: 'staff-edit-date-of-birth', birth_place: 'staff-edit-birth-place', nationality: 'staff-edit-nationality', cin: 'staff-edit-cin', cin_issued_at: 'staff-edit-cin-issued-at', address_line: 'staff-edit-address-line', governorate: 'staff-edit-governorate', delegation: 'staff-edit-delegation', phone: 'staff-edit-phone', email: 'staff-edit-email', marital_status: 'staff-edit-marital-status', dependent_children_count: 'staff-edit-dependent-children-count', emergency_contact_name: 'staff-edit-emergency-contact-name', emergency_contact_relationship: 'staff-edit-emergency-contact-relationship', emergency_contact_phone: 'staff-edit-emergency-contact-phone', emergency_contact_phone_secondary: 'staff-edit-emergency-contact-phone-secondary', employee_code: 'staff-edit-employee-code', hire_date: 'staff-edit-hire-date', position_title: 'staff-edit-position-title', department_id: 'staff-edit-department-id', employment_type: 'staff-edit-employment-type', contract_type: 'staff-edit-contract-type', contract_start_date: 'staff-edit-contract-start-date', contract_end_date: 'staff-edit-contract-end-date', probation_period: 'staff-edit-probation-period', work_location: 'staff-edit-work-location', work_schedule: 'staff-edit-work-schedule', manager_id: 'staff-edit-manager-id', user_id: 'staff-edit-user-id', status: 'staff-edit-status', exit_date: 'staff-edit-exit-date', exit_reason: 'staff-edit-exit-reason', base_salary: 'staff-edit-base-salary', fixed_bonus: 'staff-edit-fixed-bonus', variable_bonus: 'staff-edit-variable-bonus', allowances: 'staff-edit-allowances', payment_method: 'staff-edit-payment-method', bank_name: 'staff-edit-bank-name', rib: 'staff-edit-rib', cnss_number: 'staff-edit-cnss-number', cnss_affiliation_number: 'staff-edit-cnss-affiliation-number', tax_information: 'staff-edit-tax-information' };
        const syncConditionalFields = (prefix) => { const contractType = document.getElementById(`${prefix}-contract-type`); const contractEndRow = document.getElementById(`${prefix}-contract-end-row`); const statusField = document.getElementById(`${prefix}-status`); const exitDateRow = document.getElementById(`${prefix}-exit-date-row`); const exitReasonRow = document.getElementById(`${prefix}-exit-reason-row`); if (contractType && contractEndRow) contractEndRow.classList.toggle('conditional-hidden', contractType.value !== 'CDD'); const exited = statusField && statusField.value === 'exited'; if (exitDateRow) exitDateRow.classList.toggle('conditional-hidden', !exited); if (exitReasonRow) exitReasonRow.classList.toggle('conditional-hidden', !exited); };
        ['staff-create', 'staff-edit'].forEach((prefix) => { document.getElementById(`${prefix}-contract-type`)?.addEventListener('change', () => syncConditionalFields(prefix)); document.getElementById(`${prefix}-status`)?.addEventListener('change', () => syncConditionalFields(prefix)); syncConditionalFields(prefix); });
        const cropModal = document.getElementById('staff-photo-crop-modal'); const cropStage = document.getElementById('staff-photo-crop-stage'); const cropImage = document.getElementById('staff-photo-crop-image'); const cropZoom = document.getElementById('staff-photo-crop-zoom'); const cropApplyButton = document.getElementById('staff-photo-crop-apply'); const cropResetButton = document.getElementById('staff-photo-crop-reset'); const cropCancelButton = document.getElementById('staff-photo-crop-cancel'); const cropCancelTopButton = document.getElementById('staff-photo-crop-cancel-top'); const cropState = { activeInput: null, activePreview: null, originalPreviewSrc: '', originalPreviewDisplay: 'none', fileName: 'staff-photo.jpg', image: null, minScale: 1, scale: 1, offsetX: 0, offsetY: 0, dragPointerId: null, dragStartX: 0, dragStartY: 0, dragOffsetX: 0, dragOffsetY: 0 };
        const constrainCropOffsets = () => { if (!cropState.image) return; const stageSize = cropStage.clientWidth; const scaledWidth = cropState.image.naturalWidth * cropState.scale; const scaledHeight = cropState.image.naturalHeight * cropState.scale; cropState.offsetX = Math.min(0, Math.max(stageSize - scaledWidth, cropState.offsetX)); cropState.offsetY = Math.min(0, Math.max(stageSize - scaledHeight, cropState.offsetY)); };
        const renderCropImage = () => { if (!cropState.image) return; constrainCropOffsets(); cropImage.style.display = 'block'; cropImage.style.width = `${cropState.image.naturalWidth * cropState.scale}px`; cropImage.style.height = `${cropState.image.naturalHeight * cropState.scale}px`; cropImage.style.transform = `translate(${cropState.offsetX}px, ${cropState.offsetY}px)`; };
        const resetCrop = () => { if (!cropState.image) return; const stageSize = cropStage.clientWidth; cropState.minScale = Math.max(stageSize / cropState.image.naturalWidth, stageSize / cropState.image.naturalHeight); cropState.scale = cropState.minScale; cropState.offsetX = (stageSize - (cropState.image.naturalWidth * cropState.scale)) / 2; cropState.offsetY = (stageSize - (cropState.image.naturalHeight * cropState.scale)) / 2; cropZoom.value = '100'; renderCropImage(); };
        const closeCropModal = (restorePreview = false) => { if (restorePreview && cropState.activePreview) { cropState.activePreview.src = cropState.originalPreviewSrc; cropState.activePreview.style.display = cropState.originalPreviewDisplay; } if (cropState.activeInput && restorePreview) cropState.activeInput.value = ''; cropState.activeInput = null; cropState.activePreview = null; cropState.image = null; cropImage.src = ''; cropImage.style.display = 'none'; closeModal(cropModal); };
        const openCropModalForInput = (file, input, preview) => { if (!file || !input || !preview) return; cropState.activeInput = input; cropState.activePreview = preview; cropState.originalPreviewSrc = preview.getAttribute('src') || ''; cropState.originalPreviewDisplay = preview.style.display || 'none'; cropState.fileName = file.name || 'staff-photo.jpg'; const reader = new FileReader(); reader.onload = (event) => { const image = new Image(); image.onload = () => { cropState.image = image; cropImage.src = image.src; openModal('staff-photo-crop-modal'); requestAnimationFrame(resetCrop); }; image.src = event.target?.result || ''; }; reader.readAsDataURL(file); };
        const bindPhotoCropper = (inputId, previewId) => { const input = document.getElementById(inputId); const preview = document.getElementById(previewId); if (!input || !preview) return; input.addEventListener('change', () => { const file = input.files && input.files[0]; if (!file) return; openCropModalForInput(file, input, preview); }); };
        cropStage.addEventListener('pointerdown', (event) => { if (!cropState.image) return; cropState.dragPointerId = event.pointerId; cropState.dragStartX = event.clientX; cropState.dragStartY = event.clientY; cropState.dragOffsetX = cropState.offsetX; cropState.dragOffsetY = cropState.offsetY; cropImage.classList.add('dragging'); cropStage.setPointerCapture(event.pointerId); });
        cropStage.addEventListener('pointermove', (event) => { if (cropState.dragPointerId !== event.pointerId || !cropState.image) return; cropState.offsetX = cropState.dragOffsetX + (event.clientX - cropState.dragStartX); cropState.offsetY = cropState.dragOffsetY + (event.clientY - cropState.dragStartY); renderCropImage(); });
        const stopCropDrag = (event) => { if (cropState.dragPointerId !== event.pointerId) return; cropState.dragPointerId = null; cropImage.classList.remove('dragging'); if (cropStage.hasPointerCapture(event.pointerId)) cropStage.releasePointerCapture(event.pointerId); };
        cropStage.addEventListener('pointerup', stopCropDrag); cropStage.addEventListener('pointercancel', stopCropDrag); cropZoom.addEventListener('input', () => { if (!cropState.image) return; const stageSize = cropStage.clientWidth; const nextScale = cropState.minScale * (Number(cropZoom.value) / 100); const centerX = stageSize / 2; const centerY = stageSize / 2; const imageX = (centerX - cropState.offsetX) / cropState.scale; const imageY = (centerY - cropState.offsetY) / cropState.scale; cropState.scale = nextScale; cropState.offsetX = centerX - (imageX * cropState.scale); cropState.offsetY = centerY - (imageY * cropState.scale); renderCropImage(); });
        cropResetButton.addEventListener('click', resetCrop); cropCancelButton.addEventListener('click', () => closeCropModal(true)); cropCancelTopButton.addEventListener('click', () => closeCropModal(true)); cropApplyButton.addEventListener('click', () => { if (!cropState.image || !cropState.activeInput || !cropState.activePreview) return; const stageSize = cropStage.clientWidth; const sourceX = Math.max(0, (0 - cropState.offsetX) / cropState.scale); const sourceY = Math.max(0, (0 - cropState.offsetY) / cropState.scale); const sourceSize = stageSize / cropState.scale; const canvas = document.createElement('canvas'); canvas.width = 720; canvas.height = 720; const context = canvas.getContext('2d'); if (!context) return; context.drawImage(cropState.image, sourceX, sourceY, sourceSize, sourceSize, 0, 0, canvas.width, canvas.height); canvas.toBlob((blob) => { if (!blob || !cropState.activeInput || !cropState.activePreview) return; const croppedFileName = cropState.fileName.replace(/\.[^.]+$/, '') + '-crop.jpg'; const croppedFile = new File([blob], croppedFileName, { type: 'image/jpeg' }); const transfer = new DataTransfer(); transfer.items.add(croppedFile); cropState.activeInput.files = transfer.files; cropState.activePreview.src = URL.createObjectURL(blob); cropState.activePreview.style.display = 'block'; closeCropModal(false); }, 'image/jpeg', 0.92); });
        openModalButtons.forEach((button) => { button.addEventListener('click', () => { const modalId = button.getAttribute('data-open-modal'); openModal(modalId); if (modalId === 'staff-edit-modal') { document.getElementById('staff-edit-form').action = `{{ url('staff') }}/${button.dataset.staffId}`; const payload = JSON.parse(button.dataset.staff || '{}'); Object.entries(editFieldMap).forEach(([field, elementId]) => { const element = document.getElementById(elementId); if (element) element.value = payload[field] ?? ''; }); const preview = document.getElementById('staff-edit-photo-preview'); preview.src = button.dataset.staffPhotoUrl || ''; preview.style.display = preview.src ? 'block' : 'none'; syncConditionalFields('staff-edit'); } if (modalId === 'staff-delete-modal') { document.getElementById('staff-delete-form').action = `{{ url('staff') }}/${button.dataset.staffId}`; document.getElementById('staff-delete-text').textContent = `Confirmer la suppression de la fiche de "${button.dataset.staffName}" ?`; } }); });
        closeModalButtons.forEach((button) => button.addEventListener('click', () => { const modal = button.closest('.modal-overlay'); if (modal) closeModal(modal); }));
        document.querySelectorAll('.modal-overlay').forEach((modal) => { modal.addEventListener('click', (event) => { if (event.target === modal && modal !== cropModal) closeModal(modal); }); });
        cropModal.addEventListener('click', (event) => { if (event.target === cropModal) closeCropModal(true); });
        bindPhotoCropper('staff-create-photo', 'staff-create-photo-preview'); bindPhotoCropper('staff-edit-photo', 'staff-edit-photo-preview');
    </script>
@endsection
