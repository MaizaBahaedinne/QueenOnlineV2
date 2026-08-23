@extends('layouts.app')

@section('content')
    @php
        $canCreate = auth()->user()?->canFeature('reservations', 'create', 'create') ?? false;
        $canUpdate = auth()->user()?->canFeature('reservations', 'update', 'update') ?? false;
        $canDelete = auth()->user()?->canFeature('reservations', 'delete', 'delete') ?? false;
    @endphp

    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(15, 19, 26, 0.56); display: none; align-items: center; justify-content: center; padding: 18px; z-index: 80; }
        .modal-overlay.show { display: flex; }
        .modal-card { width: min(860px, 100%); max-height: 90vh; overflow: auto; background: linear-gradient(180deg, #f7fbff 0%, #ffffff 40%); border: 1px solid #d6e0ec; border-radius: 18px; padding: 18px; box-shadow: 0 14px 30px rgba(14, 39, 69, 0.16); }
        .modal-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 14px; }
        .modal-title { margin: 0; font-size: 21px; letter-spacing: .2px; color: #17324f; }
        .action-row { display: flex; gap: 8px; flex-wrap: wrap; }
        .reservation-intro { background: linear-gradient(135deg, #eaf5ff 0%, #f6fbff 100%); border: 1px solid #cfe2f5; border-radius: 12px; padding: 10px 12px; font-size: 13px; color: #21476f; margin-bottom: 12px; }
        .reservation-helper-box { border: 1px solid #d6e2ee; border-radius: 14px; padding: 14px; margin-top: 8px; background: #ffffff; }
        .reservation-step-head { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .reservation-step-badge { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 999px; background: #173f69; color: #fff; font-size: 12px; font-weight: 700; }
        .reservation-helper-title { margin: 0; font-size: 15px; font-weight: 700; color: #14304d; }
        .reservation-inline-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .reservation-inline-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .reservation-field { display: flex; flex-direction: column; gap: 5px; }
        .reservation-field label { font-size: 12px; font-weight: 700; color: #3e536b; }
        .reservation-radio-group { display: flex; gap: 14px; flex-wrap: wrap; align-items: center; min-height: 42px; }
        .reservation-radio { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #334a62; }
        .reservation-hint { margin: 8px 0 0; font-size: 12px; color: #5f6b7a; background: #f4f8fc; border: 1px solid #d7e4f2; border-radius: 9px; padding: 8px 9px; }
        .reservation-hint.is-error { color: #7a1f1f; background: #fff1f1; border-color: #f2caca; }
        .reservation-calendar-shell { display: grid; grid-template-columns: 1fr; gap: 14px; margin-top: 14px; }
        .reservation-calendar-card { border: 1px solid #d6e2ee; border-radius: 14px; background: linear-gradient(180deg, #ffffff 0%, #f7fbff 100%); padding: 12px; }
        .reservation-calendar-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
        .reservation-calendar-title { margin: 0; font-size: 16px; font-weight: 700; color: #183a5b; }
        .reservation-calendar-nav { display: inline-flex; gap: 6px; }
        .reservation-calendar-weekdays { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px; margin-bottom: 6px; }
        .reservation-calendar-weekday { font-size: 11px; text-transform: uppercase; color: #6b7f97; text-align: center; font-weight: 700; letter-spacing: .3px; }
        .reservation-calendar-grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px; }
        .reservation-day-cell { min-height: 72px; border: 1px solid #d9e3ef; border-radius: 10px; background: #fff; padding: 6px; cursor: pointer; display: flex; flex-direction: column; justify-content: space-between; transition: .15s ease; }
        .reservation-day-cell:hover { border-color: #94b2d1; transform: translateY(-1px); }
        .reservation-day-cell.is-muted { opacity: .42; background: #f8fbff; }
        .reservation-day-cell.is-today { border-color: #2d70b3; box-shadow: 0 0 0 2px rgba(45, 112, 179, .15); }
        .reservation-day-cell.is-selected { border-color: #173f69; box-shadow: 0 0 0 2px rgba(23, 63, 105, .20); }
        .reservation-day-number { font-size: 12px; font-weight: 700; color: #234869; }
        .reservation-day-events { margin-top: 4px; display: grid; gap: 3px; }
        .reservation-day-event { font-size: 10px; line-height: 1.2; padding: 2px 4px; border-radius: 6px; background: #eef5fc; color: #244f77; border-left: 3px solid #3b82f6; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .reservation-day-event-link { text-decoration: none; display: block; }
        .salle-cards-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-top: 10px; }
        .salle-card { border: 1px solid #d7dee8; border-radius: 12px; padding: 11px; cursor: pointer; background: linear-gradient(180deg, #ffffff 0%, #f9fcff 100%); transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; text-align: left; }
        .salle-card:hover { border-color: #8ca6c1; box-shadow: 0 6px 14px rgba(8, 24, 48, 0.08); transform: translateY(-1px); }
        .salle-card.is-selected { border-color: #1f5d9f; box-shadow: 0 0 0 2px rgba(31, 93, 159, 0.2); }
        .salle-card-name { font-weight: 700; margin-bottom: 4px; color: #153452; }
        .salle-card-meta { font-size: 12px; color: #5f6b7a; }
        .reservation-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 6px; }
        .reservation-quick-box { display: none; margin-top: 10px; border: 1px solid #decfba; background: #fff9f0; border-radius: 12px; padding: 10px; }
        .reservation-quick-title { margin: 0 0 6px; font-weight: 700; font-size: 13px; color: #6a4715; }
        .company-fields { display: none; }
        .company-fields.show { display: contents; }

        @media (max-width: 860px) {
            .reservation-calendar-shell,
            .reservation-inline-grid,
            .reservation-inline-grid-2,
            .salle-cards-grid { grid-template-columns: 1fr; }
        }
    </style>

    <section class="panel">
        <h1 class="panel-title">Reservations</h1>
        <p class="panel-sub">
            Liste par defaut. CRUD en modales.
            @if (! empty($reservationServiceLabel))
                Filtre service: <strong>{{ $reservationServiceLabel }}</strong>
            @endif
        </p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        @if ($errors->any())
            <div class="reservation-hint is-error" style="margin-top:10px;">
                <strong>Erreurs de validation:</strong>
                <ul style="margin: 6px 0 0 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div style="display:flex; justify-content:flex-end; margin-top:12px;">
            @if ($canCreate)
                <button type="button" class="btn btn-primary" data-open-modal="reservation-create-modal">Ajouter reservation</button>
            @endif
        </div>

        <div class="reservation-calendar-shell">
            <div class="reservation-calendar-card">
                <div class="reservation-calendar-head">
                    <h3 class="reservation-calendar-title" id="reservation-calendar-title">Calendrier</h3>
                    <div class="reservation-calendar-nav">
                        <button type="button" class="btn" id="reservation-calendar-prev">Mois prec.</button>
                        <button type="button" class="btn" id="reservation-calendar-next">Mois suiv.</button>
                    </div>
                </div>
                <div class="reservation-calendar-weekdays">
                    <div class="reservation-calendar-weekday">Lun</div>
                    <div class="reservation-calendar-weekday">Mar</div>
                    <div class="reservation-calendar-weekday">Mer</div>
                    <div class="reservation-calendar-weekday">Jeu</div>
                    <div class="reservation-calendar-weekday">Ven</div>
                    <div class="reservation-calendar-weekday">Sam</div>
                    <div class="reservation-calendar-weekday">Dim</div>
                </div>
                <div class="reservation-calendar-grid" id="reservation-calendar-grid"></div>
            </div>
        </div>
    </section>

    @if ($canCreate)
        <div class="modal-overlay" id="reservation-create-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Ajouter reservation</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" action="{{ route('reservations.store') }}" id="reservation-create-form" style="display:grid; gap:10px;">@csrf
                <input type="hidden" name="service_slug" value="{{ $reservationService !== '' ? $reservationService : 'salles' }}">
                <div class="reservation-intro">Planifie un evenement en 2 etapes: verification du creneau puis choix ou creation du client.</div>

                <div class="reservation-helper-box" style="padding-top:12px; padding-bottom:12px;">
                    <div class="reservation-field">
                        <label for="reservation-create-title">Titre de l event</label>
                        <input class="search" id="reservation-create-title" style="max-width:none;" type="text" name="title" maxlength="255" required>
                    </div>
                </div>

                <div class="reservation-helper-box">
                    <div class="reservation-step-head">
                        <span class="reservation-step-badge">1</span>
                        <p class="reservation-helper-title">Disponibilite des salles</p>
                    </div>
                    <div class="reservation-inline-grid">
                        <div class="reservation-field">
                            <label for="reservation-create-event-date">Date event</label>
                            <input class="search" style="max-width:none;" type="date" name="start_date" id="reservation-create-event-date" min="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="reservation-field">
                            <label for="reservation-create-start-time">Heure debut</label>
                            <input class="search" style="max-width:none;" type="time" name="start_time" id="reservation-create-start-time" min="08:00" max="23:59" required>
                        </div>
                        <div class="reservation-field">
                            <label for="reservation-create-end-time">Heure fin</label>
                            <input class="search" style="max-width:none;" type="time" name="end_time" id="reservation-create-end-time" min="09:00" max="23:59" required>
                        </div>
                    </div>
                    <input type="hidden" name="end_date" id="reservation-create-end-date">
                    <div class="reservation-actions">
                        <button type="button" class="btn btn-primary" id="reservation-check-availability">Verifier disponibilite</button>
                    </div>
                    <p class="reservation-hint" id="reservation-availability-status">Selectionne la date et les horaires, puis clique sur verifier.</p>
                    <input type="hidden" name="salle_id" id="reservation-create-salle-id" required>
                    <div id="reservation-salle-cards" class="salle-cards-grid"></div>
                </div>

                <div class="reservation-helper-box">
                    <div class="reservation-step-head">
                        <span class="reservation-step-badge">2</span>
                        <p class="reservation-helper-title">Fiche client</p>
                    </div>
                    <p class="reservation-hint" id="reservation-client-search-status">Saisis le CIN (8 chiffres). La recherche se fait automatiquement sur ce champ.</p>
                    <input type="hidden" name="client_id" id="reservation-create-client-id">

                    <div class="reservation-inline-grid-2" style="margin-top:10px;">
                        <div class="reservation-field">
                            <label for="reservation-client-type">Type client</label>
                            <div class="reservation-radio-group" id="reservation-client-type">
                                <label class="reservation-radio"><input type="radio" name="client_type" value="personne-physique" checked> Personne physique</label>
                                <label class="reservation-radio"><input type="radio" name="client_type" value="societe"> Societe</label>
                            </div>
                        </div>
                    </div>

                    <div class="company-fields" data-company-fields="reservation-client-type" style="margin-top:10px;">
                        <div class="reservation-inline-grid-2">
                            <div class="reservation-field">
                                <label for="reservation-client-fiscal-number">Matricule fiscale</label>
                                <input class="search" style="max-width:none;" type="text" name="fiscal_number" id="reservation-client-fiscal-number" placeholder="Matricule fiscale">
                            </div>
                            <div class="reservation-field">
                                <label for="reservation-client-company-name">Raison sociale</label>
                                <input class="search" style="max-width:none;" type="text" name="company_name" id="reservation-client-company-name" placeholder="Raison sociale">
                            </div>
                        </div>
                    </div>

                    <div class="reservation-inline-grid-2" style="margin-top:10px;">
                        <div class="reservation-field">
                            <label for="reservation-client-cin">CIN</label>
                            <input class="search" style="max-width:none;" type="text" name="cin" id="reservation-client-cin" minlength="8" maxlength="8" pattern="[0-9]{8}" inputmode="numeric" required>
                        </div>
                        <div class="reservation-field">
                            <label for="reservation-client-date-cin">Date delivrance CIN</label>
                            <input class="search" style="max-width:none;" type="date" name="date_cin" id="reservation-client-date-cin">
                        </div>
                    </div>

                    <div class="reservation-inline-grid-2" style="margin-top:10px;">
                        <div class="reservation-field">
                            <label for="reservation-client-first-name">Prenom</label>
                            <input class="search" style="max-width:none;" type="text" name="first_name" id="reservation-client-first-name" required>
                        </div>
                        <div class="reservation-field">
                            <label for="reservation-client-name">Nom</label>
                            <input class="search" style="max-width:none;" type="text" name="name" id="reservation-client-name" required>
                        </div>
                    </div>

                    <div class="reservation-inline-grid-2" style="margin-top:10px;">
                        <div class="reservation-field">
                            <label for="reservation-client-email">Email</label>
                            <input class="search" style="max-width:none;" type="email" name="email" id="reservation-client-email">
                        </div>
                    </div>

                    <div class="reservation-inline-grid-2" style="margin-top:10px;">
                        <div class="reservation-field">
                            <label for="reservation-client-address-number">N adresse</label>
                            <input class="search" style="max-width:none;" type="text" name="address_number" id="reservation-client-address-number">
                        </div>
                        <div class="reservation-field">
                            <label for="reservation-client-address-street">Rue</label>
                            <input class="search" style="max-width:none;" type="text" name="address_street" id="reservation-client-address-street">
                        </div>
                    </div>

                    <div class="reservation-inline-grid-2" style="margin-top:10px;">
                        <div class="reservation-field">
                            <label for="reservation-client-city">Ville</label>
                            <input class="search" style="max-width:none;" type="text" name="city" id="reservation-client-city">
                        </div>
                        <div class="reservation-field">
                            <label for="reservation-client-governorate">Gouvernorat</label>
                            <select class="search" style="max-width:none;" name="governorate" id="reservation-client-governorate" required>
                                <option value="">Gouvernorat</option>
                                @foreach ($governorates as $governorate)
                                    <option value="{{ $governorate }}">{{ $governorate }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="reservation-inline-grid-2" style="margin-top:10px;">
                        <div class="reservation-field">
                            <label for="reservation-client-phone">Mobile 1</label>
                            <input class="search" style="max-width:none;" type="text" name="phone" id="reservation-client-phone" required>
                        </div>
                        <div class="reservation-field">
                            <label for="reservation-client-phone-label-1">Label mobile 1</label>
                            <input class="search" style="max-width:none;" type="text" name="phone_label_1" id="reservation-client-phone-label-1">
                        </div>
                    </div>

                    <div class="reservation-inline-grid-2" style="margin-top:10px;">
                        <div class="reservation-field">
                            <label for="reservation-client-phone-2">Mobile 2</label>
                            <input class="search" style="max-width:none;" type="text" name="phone_2" id="reservation-client-phone-2">
                        </div>
                        <div class="reservation-field">
                            <label for="reservation-client-phone-label-2">Label mobile 2</label>
                            <input class="search" style="max-width:none;" type="text" name="phone_label_2" id="reservation-client-phone-label-2">
                        </div>
                    </div>

                    <div class="reservation-field" style="margin-top:10px;">
                        <label for="reservation-client-source">Source</label>
                        <select class="search" style="max-width:none;" name="source" id="reservation-client-source" required>
                            <option value="">Source</option>
                            @foreach ($sources as $source)
                                <option value="{{ $source }}">{{ $source }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="reservation-field" style="margin-top:10px;">
                        <label for="reservation-client-note">Note</label>
                        <textarea class="search" style="max-width:none; min-height:72px;" name="note" id="reservation-client-note" placeholder="Note client"></textarea>
                    </div>
                </div>

                <div class="reservation-helper-box" style="padding-top:12px; padding-bottom:12px;">
                    <div class="reservation-field">
                        <label for="reservation-total-amount">Montant total</label>
                        <input class="search" id="reservation-total-amount" style="max-width:none;" type="number" step="0.01" min="0" name="total_amount" placeholder="Ex: 2500.000">
                    </div>
                </div>

                <div class="reservation-actions">
                    <button type="submit" class="btn btn-primary">Enregistrer reservation</button>
                </div>
            </form></div></div>
    @endif

    @if ($canUpdate)
        <div class="modal-overlay" id="reservation-edit-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Modifier reservation</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <form method="POST" id="reservation-edit-form" action="#" style="display:grid; gap:10px;">@csrf @method('PATCH')
                <input class="search" style="max-width:none;" type="text" name="title" id="reservation-edit-title" maxlength="255" required placeholder="Titre de l event">
                <select class="search" style="max-width:none;" name="client_id" id="reservation-edit-client-id" required><option value="">Client</option>@foreach ($clients as $client)<option value="{{ $client->id }}">{{ $client->name }}</option>@endforeach</select>
                <select class="search" style="max-width:none;" name="salle_id" id="reservation-edit-salle-id" required><option value="">Salle</option>@foreach ($salles as $salle)<option value="{{ $salle->id }}">{{ $salle->name }}</option>@endforeach</select>
                <input class="search" style="max-width:none;" type="date" name="start_date" id="reservation-edit-start-date" min="{{ now()->toDateString() }}" required>
                <input class="search" style="max-width:none;" type="date" name="end_date" id="reservation-edit-end-date" min="{{ now()->toDateString() }}" required>
                <input class="search" style="max-width:none;" type="time" name="start_time" id="reservation-edit-start-time" min="08:00" max="23:59" required>
                <input class="search" style="max-width:none;" type="time" name="end_time" id="reservation-edit-end-time" min="09:00" max="23:59" required>
                <select class="search" style="max-width:none;" name="status" id="reservation-edit-status"><option value="pending">En attente</option><option value="confirmed">Confirmee</option><option value="cancelled">Annulee</option><option value="completed">Terminee</option></select>
                <input class="search" style="max-width:none;" type="number" step="0.01" min="0" name="total_amount" id="reservation-edit-total-amount">
                <button type="submit" class="btn btn-primary">Mettre a jour</button>
            </form></div></div>
    @endif

    @if ($canDelete)
        <div class="modal-overlay" id="reservation-delete-modal"><div class="modal-card"><div class="modal-head"><h3 class="modal-title">Supprimer reservation</h3><button type="button" class="btn" data-close-modal>Fermer</button></div>
            <p id="reservation-delete-text" class="panel-sub">Confirmer la suppression de cette reservation ?</p>
            <form method="POST" id="reservation-delete-form" action="#" style="margin-top:10px;">@csrf @method('DELETE')
                <button type="submit" class="btn">Confirmer suppression</button>
            </form></div></div>
    @endif

    <script type="application/json" id="reservation-calendar-data">{!! $reservations->map(function($reservation){
        return [
            'id' => $reservation->id,
            'service_slug' => $reservation->service_slug ?? 'salles',
            'title' => $reservation->title,
            'client' => $reservation->client?->name ?? '-',
            'salle' => $reservation->salle?->name ?? '-',
            'salle_color' => $reservation->salle?->color_code ?? '#3b82f6',
            'start_date' => $reservation->start_date,
            'end_date' => $reservation->end_date,
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
            'status' => $reservation->status ?? 'pending',
        ];
    })->values()->toJson() !!}</script>

    <script>
        const availabilityUrl = "{{ route('reservations.availability') }}";
        const clientSearchUrl = "{{ route('reservations.clients.search') }}";
        const calendarDataNode = document.getElementById('reservation-calendar-data');
        const reservationCalendarData = calendarDataNode ? JSON.parse(calendarDataNode.textContent || '[]') : [];
        const reservationShowBaseUrl = "{{ url('reservations') }}";
        const reservationCalendarTitle = document.getElementById('reservation-calendar-title');
        const reservationCalendarGrid = document.getElementById('reservation-calendar-grid');
        const reservationCalendarPrev = document.getElementById('reservation-calendar-prev');
        const reservationCalendarNext = document.getElementById('reservation-calendar-next');

        const availabilityButton = document.getElementById('reservation-check-availability');
        const availabilityStatus = document.getElementById('reservation-availability-status');
        const eventDateInput = document.getElementById('reservation-create-event-date');
        const startTimeInput = document.getElementById('reservation-create-start-time');
        const endTimeInput = document.getElementById('reservation-create-end-time');
        const endDateInput = document.getElementById('reservation-create-end-date');
        const selectedSalleInput = document.getElementById('reservation-create-salle-id');
        const salleCardsContainer = document.getElementById('reservation-salle-cards');

        const clientSearchStatus = document.getElementById('reservation-client-search-status');
        const clientIdInput = document.getElementById('reservation-create-client-id');
        const reservationClientTypeContainer = document.getElementById('reservation-client-type');
        const reservationClientFiscalNumber = document.getElementById('reservation-client-fiscal-number');
        const reservationClientCompanyName = document.getElementById('reservation-client-company-name');
        const reservationClientFirstName = document.getElementById('reservation-client-first-name');
        const reservationClientName = document.getElementById('reservation-client-name');
        const reservationClientCin = document.getElementById('reservation-client-cin');
        const reservationClientDateCin = document.getElementById('reservation-client-date-cin');
        const reservationClientEmail = document.getElementById('reservation-client-email');
        const reservationClientAddressNumber = document.getElementById('reservation-client-address-number');
        const reservationClientAddressStreet = document.getElementById('reservation-client-address-street');
        const reservationClientCity = document.getElementById('reservation-client-city');
        const reservationClientGovernorate = document.getElementById('reservation-client-governorate');
        const reservationClientPhone = document.getElementById('reservation-client-phone');
        const reservationClientPhoneLabel1 = document.getElementById('reservation-client-phone-label-1');
        const reservationClientPhone2 = document.getElementById('reservation-client-phone-2');
        const reservationClientPhoneLabel2 = document.getElementById('reservation-client-phone-label-2');
        const reservationClientSource = document.getElementById('reservation-client-source');
        const reservationClientNote = document.getElementById('reservation-client-note');
        const editStartTimeInput = document.getElementById('reservation-edit-start-time');
        const editEndTimeInput = document.getElementById('reservation-edit-end-time');

        const minimumStartTime = '08:00';
        const maximumTime = '23:59';
        const monthLabelFormatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });
        let calendarCursor = new Date();
        calendarCursor = new Date(calendarCursor.getFullYear(), calendarCursor.getMonth(), 1);

        const setStatusMessage = (targetElement, message, type = 'info') => {
            if (!targetElement) return;
            targetElement.textContent = message;
            targetElement.classList.toggle('is-error', type === 'error');
        };

        const extractErrorMessage = (payload, fallbackMessage) => {
            if (payload && payload.errors && typeof payload.errors === 'object') {
                const firstFieldErrors = Object.values(payload.errors)[0];
                if (Array.isArray(firstFieldErrors) && firstFieldErrors.length > 0) {
                    return String(firstFieldErrors[0]);
                }
            }

            if (payload && payload.message) {
                return String(payload.message);
            }

            return fallbackMessage;
        };

        const getClientTypeValue = () => {
            const checked = document.querySelector('input[name="client_type"]:checked');
            return checked ? checked.value : 'personne-physique';
        };

        const toggleCompanyFields = () => {
            const isCompany = getClientTypeValue() === 'societe';
            document.querySelectorAll('[data-company-fields="reservation-client-type"]').forEach((container) => {
                container.classList.toggle('show', isCompany);
            });
        };

        const clientFormDefaults = {
            client_type: 'personne-physique',
            fiscal_number: '',
            company_name: '',
            first_name: '',
            name: '',
            cin: '',
            date_cin: '',
            email: '',
            address_number: '',
            address_street: '',
            city: '',
            governorate: '',
            phone: '',
            phone_label_1: '',
            phone_2: '',
            phone_label_2: '',
            source: 'passager',
            note: '',
        };

        const applyClientFormData = (data = {}) => {
            const payload = { ...clientFormDefaults, ...data };

            document.querySelectorAll('input[name="client_type"]').forEach((radio) => {
                radio.checked = radio.value === (payload.client_type || 'personne-physique');
            });
            reservationClientFiscalNumber.value = payload.fiscal_number || '';
            reservationClientCompanyName.value = payload.company_name || '';
            reservationClientFirstName.value = payload.first_name || '';
            reservationClientName.value = payload.name || '';
            reservationClientCin.value = payload.cin || '';
            reservationClientDateCin.value = payload.date_cin || '';
            reservationClientEmail.value = payload.email || '';
            reservationClientAddressNumber.value = payload.address_number || '';
            reservationClientAddressStreet.value = payload.address_street || '';
            reservationClientCity.value = payload.city || '';
            reservationClientGovernorate.value = payload.governorate || '';
            reservationClientPhone.value = payload.phone || '';
            reservationClientPhoneLabel1.value = payload.phone_label_1 || '';
            reservationClientPhone2.value = payload.phone_2 || '';
            reservationClientPhoneLabel2.value = payload.phone_label_2 || '';
            reservationClientSource.value = payload.source || 'passager';
            reservationClientNote.value = payload.note || '';

            toggleCompanyFields();
        };

        const toMinutes = (timeValue) => {
            if (!timeValue || !timeValue.includes(':')) return null;
            const [hourRaw, minuteRaw] = timeValue.split(':');
            const hour = Number(hourRaw);
            const minute = Number(minuteRaw);

            if (Number.isNaN(hour) || Number.isNaN(minute)) return null;
            return (hour * 60) + minute;
        };

        const toIsoDate = (date) => {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        const parseIsoDate = (value) => {
            const [year, month, day] = String(value || '').split('-').map(Number);
            if (!year || !month || !day) return null;
            return new Date(year, month - 1, day);
        };

        const normalizeHexColor = (value, fallback = '#3b82f6') => {
            const candidate = String(value || '').trim();
            return /^#[0-9A-Fa-f]{6}$/.test(candidate) ? candidate : fallback;
        };

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const buildReservationsByDay = () => {
            const map = {};

            reservationCalendarData.forEach((reservation) => {
                const start = parseIsoDate(reservation.start_date);
                const end = parseIsoDate(reservation.end_date || reservation.start_date);

                if (!start || !end) {
                    return;
                }

                const cursor = new Date(start.getFullYear(), start.getMonth(), start.getDate());
                const last = new Date(end.getFullYear(), end.getMonth(), end.getDate());

                while (cursor <= last) {
                    const key = toIsoDate(cursor);
                    if (!map[key]) {
                        map[key] = [];
                    }
                    map[key].push(reservation);
                    cursor.setDate(cursor.getDate() + 1);
                }
            });

            return map;
        };

        const reservationsByDay = buildReservationsByDay();

        const renderCalendar = () => {
            if (!reservationCalendarTitle || !reservationCalendarGrid) {
                return;
            }

            reservationCalendarTitle.textContent = `Calendrier - ${monthLabelFormatter.format(calendarCursor)}`;

            const year = calendarCursor.getFullYear();
            const month = calendarCursor.getMonth();
            const firstDay = new Date(year, month, 1);
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const firstWeekday = (firstDay.getDay() + 6) % 7;

            reservationCalendarGrid.innerHTML = '';

            for (let i = 0; i < firstWeekday; i += 1) {
                const ghost = document.createElement('div');
                ghost.className = 'reservation-day-cell is-muted';
                reservationCalendarGrid.appendChild(ghost);
            }

            const todayIso = toIsoDate(new Date());

            for (let day = 1; day <= daysInMonth; day += 1) {
                const date = new Date(year, month, day);
                const iso = toIsoDate(date);
                const events = reservationsByDay[iso] || [];
                const eventSnippets = events.slice(0, 3).map((event) => {
                    const hour = event.start_time || '--:--';
                    const label = event.title || event.client || `Reservation #${event.id}`;
                    const color = normalizeHexColor(event.salle_color);
                    const link = `${reservationShowBaseUrl}/${event.id}`;
                    return `<a class="reservation-day-event-link" href="${link}"><div class="reservation-day-event" style="border-left-color:${color};">${escapeHtml(hour)} · ${escapeHtml(label)}</div></a>`;
                }).join('');

                const cell = document.createElement('button');
                cell.type = 'button';
                cell.className = 'reservation-day-cell';
                if (iso === todayIso) {
                    cell.classList.add('is-today');
                }
                cell.innerHTML = `
                    <span class="reservation-day-number">${day}</span>
                    ${eventSnippets ? `<div class="reservation-day-events">${eventSnippets}</div>` : ''}
                `;

                reservationCalendarGrid.appendChild(cell);
            }
        };

        if (reservationCalendarPrev) {
            reservationCalendarPrev.addEventListener('click', () => {
                calendarCursor = new Date(calendarCursor.getFullYear(), calendarCursor.getMonth() - 1, 1);
                renderCalendar();
            });
        }

        if (reservationCalendarNext) {
            reservationCalendarNext.addEventListener('click', () => {
                calendarCursor = new Date(calendarCursor.getFullYear(), calendarCursor.getMonth() + 1, 1);
                renderCalendar();
            });
        }

        renderCalendar();

        const minutesToTime = (totalMinutes) => {
            if (totalMinutes === null || totalMinutes < 0 || totalMinutes > (23 * 60 + 59)) {
                return null;
            }

            const hour = String(Math.floor(totalMinutes / 60)).padStart(2, '0');
            const minute = String(totalMinutes % 60).padStart(2, '0');
            return `${hour}:${minute}`;
        };

        const getEndTimeMinFromStart = (startValue) => {
            const startMinutes = toMinutes(startValue);

            if (startMinutes === null) {
                return '09:00';
            }

            return minutesToTime(startMinutes + 60) || '23:59';
        };

        const syncEndTimeConstraints = (startInput, endInput) => {
            if (!startInput || !endInput) return;

            const computedMin = getEndTimeMinFromStart(startInput.value);
            endInput.min = computedMin;
            endInput.max = maximumTime;

            if (endInput.value) {
                const currentEnd = toMinutes(endInput.value);
                const requiredEnd = toMinutes(computedMin);

                if (currentEnd === null || requiredEnd === null || currentEnd < requiredEnd) {
                    endInput.value = '';
                }
            }
        };

        const resetSalleSelection = () => {
            if (selectedSalleInput) {
                selectedSalleInput.value = '';
            }

            if (salleCardsContainer) {
                salleCardsContainer.innerHTML = '';
            }
        };

        const hasSelectedSalle = () => {
            return Boolean(selectedSalleInput && selectedSalleInput.value);
        };

        const renderSalleCards = (salles) => {
            if (!salleCardsContainer || !selectedSalleInput) return;

            salleCardsContainer.innerHTML = '';

            if (salles.length === 0) {
                return;
            }

            salles.forEach((salle) => {
                const card = document.createElement('button');
                card.type = 'button';
                card.className = 'salle-card';
                card.dataset.salleId = String(salle.id);
                card.innerHTML = `
                    <div class="salle-card-name">${salle.name}</div>
                    <div class="salle-card-meta">Type: ${salle.salle_type ?? 'standard'} | Capacite: ${salle.capacity ?? '-'} | Prix/jour: ${salle.price_per_day ?? '-'}</div>
                `;

                card.addEventListener('click', () => {
                    salleCardsContainer.querySelectorAll('.salle-card').forEach((node) => node.classList.remove('is-selected'));
                    card.classList.add('is-selected');
                    selectedSalleInput.value = String(salle.id);
                    setStatusMessage(clientSearchStatus, 'Salle selectionnee. Tu peux maintenant rechercher un client.');
                });

                salleCardsContainer.appendChild(card);
            });
        };

        const resetClientSelect = () => {
            if (!clientIdInput) return;
            clientIdInput.value = '';
        };

        const fillClientSelect = (clients) => {
            resetClientSelect();
            if (!Array.isArray(clients) || clients.length === 0) {
                applyClientFormData();
                return;
            }

            const firstClient = clients[0];
            if (clientIdInput) {
                clientIdInput.value = String(firstClient.id);
            }

            applyClientFormData(firstClient.data || {});
        };

        if (reservationClientTypeContainer) {
            reservationClientTypeContainer.querySelectorAll('input[name="client_type"]').forEach((radio) => {
                radio.addEventListener('change', () => toggleCompanyFields());
            });
            toggleCompanyFields();
        }

        const runCinSearch = async () => {
            if (!hasSelectedSalle()) {
                setStatusMessage(clientSearchStatus, 'Selectionne d abord une salle disponible.', 'error');
                return;
            }

            const cinValue = (reservationClientCin?.value || '').trim();
            if (!/^\d{8}$/.test(cinValue)) {
                resetClientSelect();
                applyClientFormData({ cin: cinValue });
                setStatusMessage(clientSearchStatus, 'CIN invalide: 8 chiffres obligatoires.', 'error');
                return;
            }

            setStatusMessage(clientSearchStatus, 'Recherche client par CIN...');

            try {
                const params = new URLSearchParams({ cin: cinValue });
                const response = await fetch(`${clientSearchUrl}?${params.toString()}`, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                const payload = await response.json();
                if (!response.ok) {
                    throw new Error(extractErrorMessage(payload, 'Erreur API client'));
                }

                const foundClients = payload.clients ?? [];
                if (foundClients.length === 0) {
                    resetClientSelect();
                    applyClientFormData({ cin: cinValue });
                    setStatusMessage(clientSearchStatus, 'Aucun client trouve avec ce CIN. Continue avec un nouveau client.');
                    return;
                }

                fillClientSelect(foundClients);
                setStatusMessage(clientSearchStatus, 'Client charge automatiquement a partir du CIN.');
            } catch (error) {
                setStatusMessage(clientSearchStatus, error instanceof Error ? error.message : 'Impossible de rechercher les clients pour le moment.', 'error');
            }
        };

        if (reservationClientCin) {
            reservationClientCin.addEventListener('blur', runCinSearch);
        }

        if (availabilityButton) {
            availabilityButton.addEventListener('click', async () => {
                const eventDate = eventDateInput?.value ?? '';
                const startTime = startTimeInput?.value ?? '';
                const endTime = endTimeInput?.value ?? '';

                resetSalleSelection();
                resetClientSelect();
                applyClientFormData();

                if (!eventDate || !startTime || !endTime) {
                    setStatusMessage(availabilityStatus, 'Renseigne date, heure debut et heure fin.', 'error');
                    return;
                }

                const today = new Date().toISOString().slice(0, 10);
                if (eventDate < today) {
                    setStatusMessage(availabilityStatus, 'Date event doit etre aujourd hui ou plus.', 'error');
                    return;
                }

                if (startTime < minimumStartTime || startTime > maximumTime) {
                    setStatusMessage(availabilityStatus, 'Heure debut doit etre entre 08:00 et 23:59.', 'error');
                    return;
                }

                if (startTime >= endTime) {
                    setStatusMessage(availabilityStatus, 'L heure de fin doit etre apres l heure de debut.', 'error');
                    return;
                }

                const startMinutes = toMinutes(startTime);
                const endMinutes = toMinutes(endTime);
                if (startMinutes === null || endMinutes === null || (endMinutes - startMinutes) < 60) {
                    setStatusMessage(availabilityStatus, 'Heure fin doit etre au moins heure debut + 1 heure.', 'error');
                    return;
                }

                if (endTime > maximumTime) {
                    setStatusMessage(availabilityStatus, 'Heure fin ne doit pas depasser 23:59.', 'error');
                    return;
                }

                endDateInput.value = eventDate;
                setStatusMessage(availabilityStatus, 'Recherche des salles disponibles...');

                try {
                    const params = new URLSearchParams({
                        event_date: eventDate,
                        start_time: startTime,
                        end_time: endTime,
                    });

                    const response = await fetch(`${availabilityUrl}?${params.toString()}`, {
                        headers: {
                            Accept: 'application/json',
                        },
                    });

                    const payload = await response.json();
                    if (!response.ok) {
                        throw new Error(extractErrorMessage(payload, 'Erreur API disponibilite'));
                    }
                    const availableSalles = payload.salles ?? [];

                    renderSalleCards(availableSalles);

                    if (availableSalles.length === 0) {
                        setStatusMessage(availabilityStatus, 'Aucune salle disponible pour ce creneau.');
                        return;
                    }

                    setStatusMessage(availabilityStatus, `${availableSalles.length} salle(s) disponible(s). Selectionne une salle.`);
                } catch (error) {
                    setStatusMessage(availabilityStatus, error instanceof Error ? error.message : 'Impossible de verifier la disponibilite pour le moment.', 'error');
                }
            });
        }

        if (startTimeInput && endTimeInput) {
            startTimeInput.addEventListener('change', () => syncEndTimeConstraints(startTimeInput, endTimeInput));
            startTimeInput.addEventListener('input', () => syncEndTimeConstraints(startTimeInput, endTimeInput));
            syncEndTimeConstraints(startTimeInput, endTimeInput);
        }

        if (editStartTimeInput && editEndTimeInput) {
            editStartTimeInput.addEventListener('change', () => syncEndTimeConstraints(editStartTimeInput, editEndTimeInput));
            editStartTimeInput.addEventListener('input', () => syncEndTimeConstraints(editStartTimeInput, editEndTimeInput));
            syncEndTimeConstraints(editStartTimeInput, editEndTimeInput);
        }

        const openModalButtons = document.querySelectorAll('[data-open-modal]');
        const closeModalButtons = document.querySelectorAll('[data-close-modal]');
        const openModal = (id) => { const m = document.getElementById(id); if (m) m.classList.add('show'); };
        const closeModal = (m) => m.classList.remove('show');
        openModalButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const modalId = button.getAttribute('data-open-modal');
                openModal(modalId);
                if (modalId === 'reservation-edit-modal') {
                    document.getElementById('reservation-edit-form').action = `{{ url('reservations') }}/${button.dataset.reservationId}`;
                    document.getElementById('reservation-edit-title').value = button.dataset.reservationTitle ?? '';
                    document.getElementById('reservation-edit-client-id').value = button.dataset.reservationClientId ?? '';
                    document.getElementById('reservation-edit-salle-id').value = button.dataset.reservationSalleId ?? '';
                    document.getElementById('reservation-edit-start-date').value = button.dataset.reservationStartDate ?? '';
                    document.getElementById('reservation-edit-end-date').value = button.dataset.reservationEndDate ?? '';
                    document.getElementById('reservation-edit-start-time').value = button.dataset.reservationStartTime ?? '';
                    document.getElementById('reservation-edit-end-time').value = button.dataset.reservationEndTime ?? '';
                    syncEndTimeConstraints(editStartTimeInput, editEndTimeInput);
                    document.getElementById('reservation-edit-status').value = button.dataset.reservationStatus ?? 'pending';
                    document.getElementById('reservation-edit-total-amount').value = button.dataset.reservationTotalAmount ?? '';
                }
                if (modalId === 'reservation-delete-modal') {
                    document.getElementById('reservation-delete-form').action = `{{ url('reservations') }}/${button.dataset.reservationId}`;
                }

                if (modalId === 'reservation-create-modal') {
                    resetSalleSelection();
                    resetClientSelect();
                    applyClientFormData();
                    setStatusMessage(availabilityStatus, 'Selectionne la date et les horaires, puis clique sur verifier.');
                    setStatusMessage(clientSearchStatus, 'Saisis le CIN (8 chiffres). La recherche se fait automatiquement sur ce champ.');
                    endDateInput.value = '';
                }
            });
        });
        closeModalButtons.forEach((button) => button.addEventListener('click', () => { const modal = button.closest('.modal-overlay'); if (modal) closeModal(modal); }));
        document.querySelectorAll('.modal-overlay').forEach((modal) => modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(modal); }));
    </script>
@endsection
