@extends('layouts.app')

@section('content')
    @php
        $canCreate = auth()->user()?->canFeature('staff', 'create', 'create') ?? false;
        $canUpdate = auth()->user()?->canFeature('staff', 'update', 'update') ?? false;
        $canDelete = auth()->user()?->canFeature('staff', 'delete', 'delete') ?? false;
    @endphp

    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 19, 26, 0.56); display: none; align-items: center; justify-content: center; padding: 18px; z-index: 80; }
        .modal-overlay.show { display: flex; }
        .modal-card { width: min(760px, 100%); max-height: 88vh; overflow: auto; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 16px; box-shadow: var(--shadow); }
        .modal-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
        .modal-title { margin: 0; font-size: 18px; }
        .action-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .form-grid-two { display:grid; gap:10px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .full-span { grid-column: 1 / -1; }
        .staff-avatar { width: 44px; height: 44px; border-radius: 12px; object-fit: cover; background: #e2e8f0; border: 1px solid #cbd5e1; }
        .staff-avatar-fallback { display:inline-flex; align-items:center; justify-content:center; font-weight:700; color:#1e3a5f; }
        .staff-photo-preview { width: 88px; height: 88px; border-radius: 16px; object-fit: cover; background: #e2e8f0; border: 1px solid #cbd5e1; display:block; }
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
        @media (max-width: 780px) { .form-grid-two { grid-template-columns: 1fr; } }
        @media (max-width: 860px) { .crop-layout { grid-template-columns: 1fr; } .crop-stage { width: 100%; } }
    </style>

    <section class="panel">
        <h1 class="panel-title">Staff</h1>
        <p class="panel-sub">Liste du personnel avec departement, contrat et manager.</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        @if ($errors->any())
            <div class="badge" style="margin-top:10px;background:#fff1f0;color:#9c2f2a;border-color:#f1c6c2;text-align:left;">
                <strong>Verifie les informations:</strong>
                <ul style="margin:6px 0 0 18px;padding:0;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div style="display:flex; justify-content:flex-end; margin-top:12px;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" data-open-modal="staff-create-modal">Ajouter staff</button>
            @endif
        </div>

        <div style="overflow-x:auto; margin-top: 12px;">
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Nom</th>
                        <th>Prenom</th>
                        <th>CIN</th>
                        <th>Date embauche</th>
                        <th>Poste</th>
                        <th>Departement</th>
                        <th>Type poste</th>
                        <th>Contrat</th>
                        <th>Manager</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staffMembers as $member)
                        @php
                            $managerName = trim((string) (($member->manager?->first_name ?? '') . ' ' . ($member->manager?->last_name ?? '')));
                            $initials = strtoupper(substr((string) ($member->first_name ?? ''), 0, 1) . substr((string) ($member->last_name ?? ''), 0, 1));
                        @endphp
                        <tr>
                            <td>
                                @if ($member->photo_path)
                                    <img src="{{ asset('storage/' . $member->photo_path) }}" alt="{{ $member->first_name }}" class="staff-avatar">
                                @else
                                    <span class="staff-avatar staff-avatar-fallback">{{ $initials !== '' ? $initials : 'ST' }}</span>
                                @endif
                            </td>
                            <td>{{ $member->last_name }}</td>
                            <td>{{ $member->first_name }}</td>
                            <td>{{ $member->cin ?: '-' }}</td>
                            <td>{{ $member->hire_date ? $member->hire_date->format('d/m/Y') : '-' }}</td>
                            <td>{{ $member->position_title }}</td>
                            <td>{{ $member->department?->name ?? '-' }}</td>
                            <td>{{ $member->employment_type === 'part-time' ? 'Temps partiel' : 'Permanent' }}</td>
                            <td>{{ $member->contract_type }}</td>
                            <td>{{ $managerName !== '' ? $managerName : '-' }}</td>
                            <td>
                                <div class="action-row">
                                    <a href="{{ route('staff.show', $member) }}" class="btn">Profil</a>
                                    @if ($canUpdate)
                                        <button type="button" class="btn" data-open-modal="staff-edit-modal"
                                            data-staff-id="{{ $member->id }}"
                                            data-staff-first-name="{{ $member->first_name }}"
                                            data-staff-last-name="{{ $member->last_name }}"
                                            data-staff-cin="{{ $member->cin }}"
                                            data-staff-hire-date="{{ $member->hire_date ? $member->hire_date->format('Y-m-d') : '' }}"
                                            data-staff-position-title="{{ $member->position_title }}"
                                            data-staff-department-id="{{ $member->department_id }}"
                                            data-staff-employment-type="{{ $member->employment_type }}"
                                            data-staff-contract-type="{{ $member->contract_type }}"
                                            data-staff-manager-id="{{ $member->manager_id }}"
                                            data-staff-user-id="{{ $member->user_id }}"
                                            data-staff-status="{{ $member->status ?? 'active' }}"
                                            data-staff-photo-url="{{ $member->photo_path ? asset('storage/' . $member->photo_path) : '' }}"
                                        >Modifier</button>
                                    @endif
                                    @if ($canDelete)
                                        <button type="button" class="btn" data-open-modal="staff-delete-modal"
                                            data-staff-id="{{ $member->id }}"
                                            data-staff-name="{{ trim((string) ($member->first_name . ' ' . $member->last_name)) }}"
                                        >Supprimer</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="muted">Aucun membre du staff.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canCreate)
        <div class="modal-overlay" id="staff-create-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Ajouter staff</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" action="{{ route('staff.store') }}" enctype="multipart/form-data" class="form-grid-two">@csrf
                <div class="full-span"><label>Photo</label><img src="" alt="Apercu photo" class="staff-photo-preview" id="staff-create-photo-preview" style="display:none;margin:0 0 8px;"><input class="search" style="max-width:none;" type="file" name="photo" id="staff-create-photo" accept="image/*"></div>
                <input class="search" style="max-width:none;" type="text" name="last_name" placeholder="Nom" required>
                <input class="search" style="max-width:none;" type="text" name="first_name" placeholder="Prenom" required>
                <input class="search" style="max-width:none;" type="text" name="cin" placeholder="CIN (8 chiffres, optionnel)" minlength="8" maxlength="8" pattern="[0-9]{8}" inputmode="numeric">
                <input class="search" style="max-width:none;" type="date" name="hire_date" placeholder="Date d'embauche">
                <input class="search" style="max-width:none;" type="text" name="position_title" placeholder="Poste" required>
                <select class="search" style="max-width:none;" name="department_id">
                    <option value="">Departement existant</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
                <input class="search" style="max-width:none;" type="text" name="department_name" placeholder="Ou nouveau departement">
                <select class="search" style="max-width:none;" name="employment_type" required>
                    <option value="permanent">Permanent</option>
                    <option value="part-time">Temps partiel</option>
                </select>
                <select class="search" style="max-width:none;" name="contract_type" required>
                    <option value="CDI">CDI</option>
                    <option value="CDD">CDD</option>
                    <option value="Freelance">Freelance</option>
                </select>
                <select class="search full-span" style="max-width:none;" name="manager_id">
                    <option value="">Manager</option>
                    @foreach ($managers as $manager)
                        <option value="{{ $manager->id }}">{{ trim((string) ($manager->first_name . ' ' . $manager->last_name)) }}</option>
                    @endforeach
                </select>
                <select class="search full-span" style="max-width:none;" name="user_id">
                    <option value="">Compte utilisateur lie (optionnel)</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}{{ $user->role?->name ? ' - ' . $user->role->name : '' }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary full-span">Enregistrer</button>
            </form></div></div>
    @endif

    @if ($canUpdate)
        <div class="modal-overlay" id="staff-edit-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Modifier staff</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" id="staff-edit-form" action="#" enctype="multipart/form-data" class="form-grid-two">@csrf @method('PATCH')
                <div class="full-span"><label>Photo</label><img src="" alt="Apercu photo" class="staff-photo-preview" id="staff-edit-photo-preview" style="display:none;margin:0 0 8px;"><input class="search" style="max-width:none;" type="file" name="photo" id="staff-edit-photo" accept="image/*"></div>
                <input class="search" style="max-width:none;" type="text" name="last_name" id="staff-edit-last-name" required>
                <input class="search" style="max-width:none;" type="text" name="first_name" id="staff-edit-first-name" required>
                <input class="search" style="max-width:none;" type="text" name="cin" id="staff-edit-cin" minlength="8" maxlength="8" pattern="[0-9]{8}" inputmode="numeric" placeholder="CIN (8 chiffres, optionnel)">
                <input class="search" style="max-width:none;" type="date" name="hire_date" id="staff-edit-hire-date">
                <input class="search" style="max-width:none;" type="text" name="position_title" id="staff-edit-position-title" required>
                <select class="search" style="max-width:none;" name="department_id" id="staff-edit-department-id">
                    <option value="">Departement existant</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
                <input class="search" style="max-width:none;" type="text" name="department_name" id="staff-edit-department-name" placeholder="Ou nouveau departement">
                <select class="search" style="max-width:none;" name="employment_type" id="staff-edit-employment-type" required>
                    <option value="permanent">Permanent</option>
                    <option value="part-time">Temps partiel</option>
                </select>
                <select class="search" style="max-width:none;" name="contract_type" id="staff-edit-contract-type" required>
                    <option value="CDI">CDI</option>
                    <option value="CDD">CDD</option>
                    <option value="Freelance">Freelance</option>
                </select>
                <select class="search" style="max-width:none;" name="status" id="staff-edit-status">
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </select>
                <select class="search full-span" style="max-width:none;" name="manager_id" id="staff-edit-manager-id">
                    <option value="">Manager</option>
                    @foreach ($managers as $manager)
                        <option value="{{ $manager->id }}">{{ trim((string) ($manager->first_name . ' ' . $manager->last_name)) }}</option>
                    @endforeach
                </select>
                <select class="search full-span" style="max-width:none;" name="user_id" id="staff-edit-user-id">
                    <option value="">Compte utilisateur lie (optionnel)</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}{{ $user->role?->name ? ' - ' . $user->role->name : '' }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary full-span">Mettre a jour</button>
            </form></div></div>
    @endif

    @if ($canDelete)
        <div class="modal-overlay" id="staff-delete-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Supprimer staff</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <p id="staff-delete-text" class="panel-sub"></p>
            <form method="POST" id="staff-delete-form" action="#" style="margin-top:10px;">@csrf @method('DELETE')
                <button type="submit" class="btn">Confirmer suppression</button>
            </form></div></div>
    @endif

    <div class="modal-overlay" id="staff-photo-crop-modal">
        <div class="modal-card crop-modal-card">
            <div class="modal-head">
                <div>
                    <h3 class="modal-title">Recadrer la photo</h3>
                    <p class="panel-sub" style="margin:4px 0 0;">Format final carre 1:1 pour le profil staff.</p>
                </div>
                <button type="button" class="btn" id="staff-photo-crop-cancel-top">Fermer</button>
            </div>

            <div class="crop-layout">
                <div class="crop-stage" id="staff-photo-crop-stage">
                    <img src="" alt="Recadrage photo staff" id="staff-photo-crop-image" class="crop-image" style="display:none;">
                </div>

                <div class="crop-sidebar">
                    <p class="crop-help">Glisse l image pour la positionner dans le cadre, puis ajuste le zoom. La photo enregistree sera recadree en carre 1:1.</p>
                    <div>
                        <label for="staff-photo-crop-zoom" style="display:block; margin-bottom:8px; font-weight:600;">Zoom</label>
                        <input type="range" id="staff-photo-crop-zoom" class="crop-slider" min="100" max="400" step="1" value="100">
                    </div>
                    <div class="crop-actions">
                        <button type="button" class="btn" id="staff-photo-crop-reset">Reinitialiser</button>
                        <button type="button" class="btn" id="staff-photo-crop-cancel">Annuler</button>
                        <button type="button" class="btn btn-primary" id="staff-photo-crop-apply">Utiliser cette photo</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const openModalButtons = document.querySelectorAll('[data-open-modal]');
        const closeModalButtons = document.querySelectorAll('[data-close-modal]');
        const openModal = (id) => { const m = document.getElementById(id); if (m) m.classList.add('show'); };
        const closeModal = (m) => m.classList.remove('show');
        const cropModal = document.getElementById('staff-photo-crop-modal');
        const cropStage = document.getElementById('staff-photo-crop-stage');
        const cropImage = document.getElementById('staff-photo-crop-image');
        const cropZoom = document.getElementById('staff-photo-crop-zoom');
        const cropApplyButton = document.getElementById('staff-photo-crop-apply');
        const cropResetButton = document.getElementById('staff-photo-crop-reset');
        const cropCancelButton = document.getElementById('staff-photo-crop-cancel');
        const cropCancelTopButton = document.getElementById('staff-photo-crop-cancel-top');
        const cropState = {
            activeInput: null,
            activePreview: null,
            originalPreviewSrc: '',
            originalPreviewDisplay: 'none',
            fileName: 'staff-photo.jpg',
            image: null,
            minScale: 1,
            scale: 1,
            offsetX: 0,
            offsetY: 0,
            dragPointerId: null,
            dragStartX: 0,
            dragStartY: 0,
            dragOffsetX: 0,
            dragOffsetY: 0,
        };

        const constrainCropOffsets = () => {
            if (!cropState.image) {
                return;
            }

            const stageSize = cropStage.clientWidth;
            const scaledWidth = cropState.image.naturalWidth * cropState.scale;
            const scaledHeight = cropState.image.naturalHeight * cropState.scale;
            const minX = stageSize - scaledWidth;
            const minY = stageSize - scaledHeight;

            cropState.offsetX = Math.min(0, Math.max(minX, cropState.offsetX));
            cropState.offsetY = Math.min(0, Math.max(minY, cropState.offsetY));
        };

        const renderCropImage = () => {
            if (!cropState.image) {
                return;
            }

            constrainCropOffsets();
            cropImage.style.display = 'block';
            cropImage.style.width = `${cropState.image.naturalWidth * cropState.scale}px`;
            cropImage.style.height = `${cropState.image.naturalHeight * cropState.scale}px`;
            cropImage.style.transform = `translate(${cropState.offsetX}px, ${cropState.offsetY}px)`;
        };

        const resetCrop = () => {
            if (!cropState.image) {
                return;
            }

            const stageSize = cropStage.clientWidth;
            const baseScale = Math.max(stageSize / cropState.image.naturalWidth, stageSize / cropState.image.naturalHeight);
            cropState.minScale = baseScale;
            cropState.scale = baseScale;
            cropState.offsetX = (stageSize - (cropState.image.naturalWidth * cropState.scale)) / 2;
            cropState.offsetY = (stageSize - (cropState.image.naturalHeight * cropState.scale)) / 2;
            cropZoom.value = '100';
            renderCropImage();
        };

        const closeCropModal = (restorePreview = false) => {
            if (restorePreview && cropState.activePreview) {
                cropState.activePreview.src = cropState.originalPreviewSrc;
                cropState.activePreview.style.display = cropState.originalPreviewDisplay;
            }

            if (cropState.activeInput && restorePreview) {
                cropState.activeInput.value = '';
            }

            cropState.activeInput = null;
            cropState.activePreview = null;
            cropState.image = null;
            cropImage.src = '';
            cropImage.style.display = 'none';
            closeModal(cropModal);
        };

        const openCropModalForInput = (file, input, preview) => {
            if (!file || !input || !preview) {
                return;
            }

            cropState.activeInput = input;
            cropState.activePreview = preview;
            cropState.originalPreviewSrc = preview.getAttribute('src') || '';
            cropState.originalPreviewDisplay = preview.style.display || 'none';
            cropState.fileName = file.name || 'staff-photo.jpg';

            const reader = new FileReader();
            reader.onload = (event) => {
                const image = new Image();
                image.onload = () => {
                    cropState.image = image;
                    cropImage.src = image.src;
                    openModal('staff-photo-crop-modal');
                    requestAnimationFrame(() => {
                        resetCrop();
                    });
                };
                image.src = event.target?.result || '';
            };
            reader.readAsDataURL(file);
        };

        const bindPhotoCropper = (inputId, previewId) => {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            if (!input || !preview) {
                return;
            }

            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                if (!file) {
                    return;
                }

                openCropModalForInput(file, input, preview);
            });
        };

        cropStage.addEventListener('pointerdown', (event) => {
            if (!cropState.image) {
                return;
            }

            cropState.dragPointerId = event.pointerId;
            cropState.dragStartX = event.clientX;
            cropState.dragStartY = event.clientY;
            cropState.dragOffsetX = cropState.offsetX;
            cropState.dragOffsetY = cropState.offsetY;
            cropImage.classList.add('dragging');
            cropStage.setPointerCapture(event.pointerId);
        });

        cropStage.addEventListener('pointermove', (event) => {
            if (cropState.dragPointerId !== event.pointerId || !cropState.image) {
                return;
            }

            cropState.offsetX = cropState.dragOffsetX + (event.clientX - cropState.dragStartX);
            cropState.offsetY = cropState.dragOffsetY + (event.clientY - cropState.dragStartY);
            renderCropImage();
        });

        const stopCropDrag = (event) => {
            if (cropState.dragPointerId !== event.pointerId) {
                return;
            }

            cropState.dragPointerId = null;
            cropImage.classList.remove('dragging');
            if (cropStage.hasPointerCapture(event.pointerId)) {
                cropStage.releasePointerCapture(event.pointerId);
            }
        };

        cropStage.addEventListener('pointerup', stopCropDrag);
        cropStage.addEventListener('pointercancel', stopCropDrag);

        cropZoom.addEventListener('input', () => {
            if (!cropState.image) {
                return;
            }

            const stageSize = cropStage.clientWidth;
            const scaleFactor = Number(cropZoom.value) / 100;
            const nextScale = cropState.minScale * scaleFactor;
            const centerX = stageSize / 2;
            const centerY = stageSize / 2;
            const imageX = (centerX - cropState.offsetX) / cropState.scale;
            const imageY = (centerY - cropState.offsetY) / cropState.scale;

            cropState.scale = nextScale;
            cropState.offsetX = centerX - (imageX * cropState.scale);
            cropState.offsetY = centerY - (imageY * cropState.scale);

            renderCropImage();
        });

        cropResetButton.addEventListener('click', () => {
            resetCrop();
        });

        cropCancelButton.addEventListener('click', () => {
            closeCropModal(true);
        });

        cropCancelTopButton.addEventListener('click', () => {
            closeCropModal(true);
        });

        cropApplyButton.addEventListener('click', () => {
            if (!cropState.image || !cropState.activeInput || !cropState.activePreview) {
                return;
            }

            const stageSize = cropStage.clientWidth;
            const sourceX = Math.max(0, (0 - cropState.offsetX) / cropState.scale);
            const sourceY = Math.max(0, (0 - cropState.offsetY) / cropState.scale);
            const sourceSize = stageSize / cropState.scale;
            const canvas = document.createElement('canvas');
            canvas.width = 720;
            canvas.height = 720;

            const context = canvas.getContext('2d');
            if (!context) {
                return;
            }

            context.drawImage(
                cropState.image,
                sourceX,
                sourceY,
                sourceSize,
                sourceSize,
                0,
                0,
                canvas.width,
                canvas.height
            );

            canvas.toBlob((blob) => {
                if (!blob || !cropState.activeInput || !cropState.activePreview) {
                    return;
                }

                const croppedFileName = cropState.fileName.replace(/\.[^.]+$/, '') + '-crop.jpg';
                const croppedFile = new File([blob], croppedFileName, { type: 'image/jpeg' });
                const transfer = new DataTransfer();
                transfer.items.add(croppedFile);
                cropState.activeInput.files = transfer.files;

                cropState.activePreview.src = URL.createObjectURL(blob);
                cropState.activePreview.style.display = 'block';
                closeCropModal(false);
            }, 'image/jpeg', 0.92);
        });

        openModalButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const modalId = button.getAttribute('data-open-modal');
                openModal(modalId);

                if (modalId === 'staff-edit-modal') {
                    document.getElementById('staff-edit-form').action = `{{ url('staff') }}/${button.dataset.staffId}`;
                    document.getElementById('staff-edit-last-name').value = button.dataset.staffLastName ?? '';
                    document.getElementById('staff-edit-first-name').value = button.dataset.staffFirstName ?? '';
                    document.getElementById('staff-edit-cin').value = button.dataset.staffCin ?? '';
                    document.getElementById('staff-edit-hire-date').value = button.dataset.staffHireDate ?? '';
                    document.getElementById('staff-edit-position-title').value = button.dataset.staffPositionTitle ?? '';
                    document.getElementById('staff-edit-department-id').value = button.dataset.staffDepartmentId ?? '';
                    document.getElementById('staff-edit-department-name').value = '';
                    document.getElementById('staff-edit-employment-type').value = button.dataset.staffEmploymentType ?? 'permanent';
                    document.getElementById('staff-edit-contract-type').value = button.dataset.staffContractType ?? 'CDI';
                    document.getElementById('staff-edit-manager-id').value = button.dataset.staffManagerId ?? '';
                    document.getElementById('staff-edit-user-id').value = button.dataset.staffUserId ?? '';
                    document.getElementById('staff-edit-status').value = button.dataset.staffStatus ?? 'active';
                    const preview = document.getElementById('staff-edit-photo-preview');
                    if (preview) {
                        preview.src = button.dataset.staffPhotoUrl ?? '';
                        preview.style.display = preview.src ? 'block' : 'none';
                    }
                }

                if (modalId === 'staff-delete-modal') {
                    document.getElementById('staff-delete-form').action = `{{ url('staff') }}/${button.dataset.staffId}`;
                    document.getElementById('staff-delete-text').textContent = `Confirmer la suppression de "${button.dataset.staffName}" ?`;
                }
            });
        });

        closeModalButtons.forEach((button) => button.addEventListener('click', () => {
            const modal = button.closest('.modal-overlay');
            if (modal) closeModal(modal);
        }));

        document.querySelectorAll('.modal-overlay').forEach((modal) => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) closeModal(modal);
            });
        });

        cropModal.addEventListener('click', (event) => {
            if (event.target === cropModal) {
                closeCropModal(true);
            }
        });

        bindPhotoCropper('staff-create-photo', 'staff-create-photo-preview');
        bindPhotoCropper('staff-edit-photo', 'staff-edit-photo-preview');
    </script>
@endsection
