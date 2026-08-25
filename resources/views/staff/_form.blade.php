@php
    $prefixId = 'staff-' . $prefix;
@endphp

<div class="staff-form">
    <section class="staff-section">
        <div class="staff-section-head">
            <h3 class="staff-section-title">1. Informations personnelles</h3>
        </div>
        <div class="staff-section-body">
            <div class="field full-span">
                <label>Photo</label>
                <img src="" alt="Apercu photo" class="staff-photo-preview" id="{{ $prefixId }}-photo-preview" style="display:none; margin:0 0 8px;">
                <input class="search" style="max-width:none;" type="file" name="photo" id="{{ $prefixId }}-photo" accept="image/*">
            </div>
            <div class="form-grid-3">
                <div class="field"><label>Nom</label><input class="search" style="max-width:none;" type="text" name="last_name" id="{{ $prefixId }}-last-name" required></div>
                <div class="field"><label>Prenom</label><input class="search" style="max-width:none;" type="text" name="first_name" id="{{ $prefixId }}-first-name" required></div>
                <div class="field"><label>Date de naissance</label><input class="search" style="max-width:none;" type="date" name="date_of_birth" id="{{ $prefixId }}-date-of-birth"></div>
                <div class="field"><label>Lieu de naissance</label><input class="search" style="max-width:none;" type="text" name="birth_place" id="{{ $prefixId }}-birth-place"></div>
                <div class="field"><label>Nationalite</label><input class="search" style="max-width:none;" type="text" name="nationality" id="{{ $prefixId }}-nationality"></div>
                <div class="field"><label>CIN / carte d identite</label><input class="search" style="max-width:none;" type="text" name="cin" id="{{ $prefixId }}-cin" minlength="8" maxlength="8" pattern="[0-9]{8}" inputmode="numeric" placeholder="8 chiffres, optionnel"></div>
                <div class="field"><label>Date de delivrance CIN</label><input class="search" style="max-width:none;" type="date" name="cin_issued_at" id="{{ $prefixId }}-cin-issued-at"></div>
                <div class="field"><label>Telephone</label><input class="search" style="max-width:none;" type="text" name="phone" id="{{ $prefixId }}-phone"></div>
                <div class="field"><label>Email</label><input class="search" style="max-width:none;" type="email" name="email" id="{{ $prefixId }}-email"></div>
                <div class="field">
                    <label>Situation familiale</label>
                    <select class="search" style="max-width:none;" name="marital_status" id="{{ $prefixId }}-marital-status">
                        <option value="">Selectionner</option>
                        @foreach ($maritalStatusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label>Enfants a charge</label><input class="search" style="max-width:none;" type="number" min="0" name="dependent_children_count" id="{{ $prefixId }}-dependent-children-count"></div>
                <div class="field"><label>Gouvernorat</label><input class="search" style="max-width:none;" type="text" name="governorate" id="{{ $prefixId }}-governorate"></div>
                <div class="field"><label>Delegation</label><input class="search" style="max-width:none;" type="text" name="delegation" id="{{ $prefixId }}-delegation"></div>
            </div>
            <div class="field full-span"><label>Adresse complete</label><textarea class="search" style="max-width:none;" name="address_line" id="{{ $prefixId }}-address-line"></textarea></div>
        </div>
    </section>

    <section class="staff-section">
        <div class="staff-section-head">
            <h3 class="staff-section-title">2. Contact d urgence</h3>
        </div>
        <div class="staff-section-body form-grid-2">
            <div class="field"><label>Nom et prenom</label><input class="search" style="max-width:none;" type="text" name="emergency_contact_name" id="{{ $prefixId }}-emergency-contact-name"></div>
            <div class="field"><label>Lien avec l employe</label><input class="search" style="max-width:none;" type="text" name="emergency_contact_relationship" id="{{ $prefixId }}-emergency-contact-relationship"></div>
            <div class="field"><label>Telephone</label><input class="search" style="max-width:none;" type="text" name="emergency_contact_phone" id="{{ $prefixId }}-emergency-contact-phone"></div>
            <div class="field"><label>Telephone secondaire</label><input class="search" style="max-width:none;" type="text" name="emergency_contact_phone_secondary" id="{{ $prefixId }}-emergency-contact-phone-secondary"></div>
        </div>
    </section>

    <section class="staff-section">
        <div class="staff-section-head">
            <h3 class="staff-section-title">3. Informations professionnelles</h3>
        </div>
        <div class="staff-section-body">
            <div class="form-grid-3">
                <div class="field"><label>Matricule employe</label><input class="search" style="max-width:none;" type="text" name="employee_code" id="{{ $prefixId }}-employee-code"></div>
                <div class="field"><label>Poste / fonction</label><input class="search" style="max-width:none;" type="text" name="position_title" id="{{ $prefixId }}-position-title" required></div>
                <div class="field"><label>Date d entree</label><input class="search" style="max-width:none;" type="date" name="hire_date" id="{{ $prefixId }}-hire-date"></div>
                <div class="field">
                    <label>Departement existant</label>
                    <select class="search" style="max-width:none;" name="department_id" id="{{ $prefixId }}-department-id">
                        <option value="">Selectionner</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label>Nouveau departement</label><input class="search" style="max-width:none;" type="text" name="department_name" id="{{ $prefixId }}-department-name"></div>
                <div class="field">
                    <label>Responsable hierarchique</label>
                    <select class="search" style="max-width:none;" name="manager_id" id="{{ $prefixId }}-manager-id">
                        <option value="">Aucun</option>
                        @foreach ($managers as $manager)
                            <option value="{{ $manager->id }}">{{ trim((string) ($manager->first_name . ' ' . $manager->last_name)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label>Type de poste</label><select class="search" style="max-width:none;" name="employment_type" id="{{ $prefixId }}-employment-type" required><option value="permanent">Permanent</option><option value="part-time">Temps partiel</option></select></div>
                <div class="field"><label>Type de contrat</label><select class="search" style="max-width:none;" name="contract_type" id="{{ $prefixId }}-contract-type" required><option value="CDI">CDI</option><option value="CDD">CDD</option><option value="Freelance">Autre / Freelance</option></select></div>
                <div class="field"><label>Date debut contrat</label><input class="search" style="max-width:none;" type="date" name="contract_start_date" id="{{ $prefixId }}-contract-start-date"></div>
                <div class="field" id="{{ $prefixId }}-contract-end-row"><label>Date fin si CDD</label><input class="search" style="max-width:none;" type="date" name="contract_end_date" id="{{ $prefixId }}-contract-end-date"></div>
                <div class="field"><label>Periode d essai</label><input class="search" style="max-width:none;" type="text" name="probation_period" id="{{ $prefixId }}-probation-period" placeholder="Ex: 3 mois"></div>
                <div class="field"><label>Lieu de travail</label><input class="search" style="max-width:none;" type="text" name="work_location" id="{{ $prefixId }}-work-location"></div>
                <div class="field"><label>Horaires de travail</label><input class="search" style="max-width:none;" type="text" name="work_schedule" id="{{ $prefixId }}-work-schedule" placeholder="Ex: Lun-Ven 8h-17h"></div>
                <div class="field">
                    <label>Compte utilisateur lie</label>
                    <select class="search" style="max-width:none;" name="user_id" id="{{ $prefixId }}-user-id">
                        <option value="">Aucun compte lie</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} - {{ $user->email }}{{ $user->role?->name ? ' - ' . $user->role->name : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label>Statut</label><select class="search" style="max-width:none;" name="status" id="{{ $prefixId }}-status"><option value="active">Actif</option><option value="suspended">Suspendu</option><option value="exited">Sorti</option></select></div>
                <div class="field" id="{{ $prefixId }}-exit-date-row"><label>Date de sortie</label><input class="search" style="max-width:none;" type="date" name="exit_date" id="{{ $prefixId }}-exit-date"></div>
            </div>
            <div class="field full-span" id="{{ $prefixId }}-exit-reason-row"><label>Motif de sortie</label><textarea class="search" style="max-width:none;" name="exit_reason" id="{{ $prefixId }}-exit-reason"></textarea></div>
        </div>
    </section>

    <section class="staff-section">
        <div class="staff-section-head">
            <h3 class="staff-section-title">4. Informations paie</h3>
            <p class="staff-section-note">Champs sensibles: limiter l acces aux responsables concernes.</p>
        </div>
        <div class="staff-section-body">
            <div class="form-grid-3">
                <div class="field"><label>Salaire de base</label><input class="search" style="max-width:none;" type="number" min="0" step="0.001" name="base_salary" id="{{ $prefixId }}-base-salary"></div>
                <div class="field"><label>Prime fixe</label><input class="search" style="max-width:none;" type="number" min="0" step="0.001" name="fixed_bonus" id="{{ $prefixId }}-fixed-bonus"></div>
                <div class="field"><label>Primes variables</label><input class="search" style="max-width:none;" type="number" min="0" step="0.001" name="variable_bonus" id="{{ $prefixId }}-variable-bonus"></div>
                <div class="field"><label>Indemnites</label><input class="search" style="max-width:none;" type="number" min="0" step="0.001" name="allowances" id="{{ $prefixId }}-allowances"></div>
                <div class="field">
                    <label>Mode de paiement</label>
                    <select class="search" style="max-width:none;" name="payment_method" id="{{ $prefixId }}-payment-method">
                        <option value="">Selectionner</option>
                        @foreach ($paymentMethodOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field"><label>Banque</label><input class="search" style="max-width:none;" type="text" name="bank_name" id="{{ $prefixId }}-bank-name"></div>
                <div class="field"><label>RIB</label><input class="search" style="max-width:none;" type="text" name="rib" id="{{ $prefixId }}-rib"></div>
                <div class="field"><label>CNSS</label><input class="search" style="max-width:none;" type="text" name="cnss_number" id="{{ $prefixId }}-cnss-number"></div>
                <div class="field"><label>Numero affiliation CNSS</label><input class="search" style="max-width:none;" type="text" name="cnss_affiliation_number" id="{{ $prefixId }}-cnss-affiliation-number"></div>
            </div>
            <div class="field full-span"><label>Regime fiscal / informations paie</label><textarea class="search" style="max-width:none;" name="tax_information" id="{{ $prefixId }}-tax-information"></textarea></div>
        </div>
    </section>
</div>
