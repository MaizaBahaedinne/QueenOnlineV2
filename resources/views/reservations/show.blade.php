@extends('layouts.app')

@section('content')
    @php
        $status = (string) ($reservation->status ?? 'pending');
        $statusLabel = match ($status) {
            'confirmed' => 'Confirmee',
            'cancelled' => 'Annulee',
            'completed' => 'Terminee',
            default => 'En attente',
        };
        $statusTone = match ($status) {
            'confirmed' => 'ok',
            'completed' => 'info',
            'cancelled' => 'danger',
            default => 'warn',
        };
        $clientFullName = trim((string) ($reservation->client?->first_name . ' ' . $reservation->client?->name));
        $clientFullName = $clientFullName !== '' ? $clientFullName : ($reservation->client?->name ?? '-');
        $canUpdateReservation = auth()->user()?->canFeature('reservations', 'update', 'update') ?? false;
        $canCreateReservation = auth()->user()?->canFeature('reservations', 'create', 'create') ?? false;
        $canCreatePayment = auth()->user()?->canFeature('payments', 'create', 'create') ?? false;
        $totalAmount = (float) ($reservation->total_amount ?? 0);
        $totalPaid = (float) $reservation->payments->sum('amount');
        $remainingAmount = max($totalAmount - $totalPaid, 0);
        $clientCreditBalance = (float) ($clientCreditBalance ?? 0);
        $paymentCount = $reservation->payments->count();
        $nextPhase = match (true) {
            $paymentCount === 0 => 'avance',
            $remainingAmount <= 0 => 'reste',
            $paymentCount === 1 => 'partie-1',
            $paymentCount === 2 => 'partie-2',
            $paymentCount === 3 => 'partie-3',
            default => 'reste',
        };
        $isSalleReservation = ($reservation->service_slug ?? 'salles') === 'salles';
        $clientAddress = trim(collect([
            $reservation->client?->address_number,
            $reservation->client?->address_street,
            $reservation->client?->city,
            $reservation->client?->governorate,
        ])->filter()->implode(', '));
        $clientAddress = $clientAddress !== '' ? $clientAddress : '-';
        $currentSalleColor = (string) ($reservation->salle?->color_code ?? '#8ea9c4');
        $reservationTypeLabel = match ((string) ($reservation->service_slug ?? 'salles')) {
            'salles' => 'Salle',
            'troupe-musicale' => 'Troupe musicale',
            'photographe' => 'Photographe',
            'chanteur' => 'Chanteur',
            'notaire' => 'Notaire',
            'animation' => 'Animation',
            'voiture' => 'Voiture',
            default => 'Autre',
        };
        $reservationScopeLabel = (string) ($reservationScopeLabel ?? (($reservation->service_slug ?? 'salles') === 'salles' ? 'Interne' : 'Externe'));
        $creditServiceLabel = (string) ($creditServiceLabel ?? $reservationTypeLabel);
        $linkedAdditionalServiceRows = $reservation->additionalServices
            ->filter(fn ($row) => $row->linkedReservation)
            ->values();
        $activeLinkedAdditionalServiceRows = $linkedAdditionalServiceRows
            ->filter(fn ($row) => ($row->linkedReservation->status ?? null) !== 'cancelled')
            ->values();
        $staffOptions = $staffOptions ?? collect();
        $gerantAffectation = $reservation->serviceAffectations->firstWhere('affectation', 'gerant');
        $serveurAffectations = $reservation->serviceAffectations->where('affectation', 'serveur')->values();
        $annimateurAffectations = $reservation->serviceAffectations->where('affectation', 'annimateur')->values();
        $femmeMenageAffectations = $reservation->serviceAffectations->where('affectation', 'femme-menage')->values();
        $agentSecuriteAffectations = $reservation->serviceAffectations->where('affectation', 'agent-securite')->values();
        $selectedServeurUserIds = $serveurAffectations->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->values();
        $selectedAnnimateurUserIds = $annimateurAffectations->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->values();
        $selectedFemmeMenageUserIds = $femmeMenageAffectations->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->values();
        $selectedAgentSecuriteUserIds = $agentSecuriteAffectations->pluck('user_id')->filter()->map(fn ($id) => (int) $id)->values();
        $staffByUserId = $staffOptions->keyBy('user_id');
        $tvaRate = 0.19;
        $invoiceTotalTtc = (float) ($reservation->total_amount ?? 0);
        $parallelBusyStaffUserIds = collect($parallelBusyStaffUserIds ?? [])->map(fn ($id) => (int) $id)->unique()->values();
    @endphp

    <style>
        .reservation-show {
            --rs-bg-a: #f6fbff;
            --rs-bg-b: #eef6ff;
            --rs-card: #ffffff;
            --rs-line: #dbe7f4;
            --rs-text: #14314d;
            --rs-muted: #55708c;
            --rs-shadow: 0 14px 36px rgba(20, 49, 77, 0.10);
            display: grid;
            gap: 14px;
        }

        .reservation-flash {
            border: 1px solid #d4e7d8;
            border-radius: 12px;
            padding: 10px 12px;
            background: #effaf1;
            color: #1a5b2a;
            font-size: 13px;
            font-weight: 600;
        }

        .reservation-error-box {
            border: 1px solid #f1c6c2;
            border-radius: 12px;
            padding: 10px 12px;
            background: #fff1f0;
            color: #9c2f2a;
            font-size: 13px;
        }

        .reservation-error-box ul {
            margin: 6px 0 0 18px;
            padding: 0;
        }

        .reservation-show-hero {
            border: 1px solid #d5e3f2;
            border-radius: 18px;
            padding: 18px;
            background:
                radial-gradient(circle at 10% 15%, rgba(64, 162, 230, 0.20) 0%, rgba(64, 162, 230, 0) 48%),
                radial-gradient(circle at 88% 18%, rgba(14, 111, 186, 0.18) 0%, rgba(14, 111, 186, 0) 40%),
                linear-gradient(145deg, var(--rs-bg-a) 0%, var(--rs-bg-b) 100%);
            box-shadow: var(--rs-shadow);
            display: flex;
            gap: 14px;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .reservation-top-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 14px;
            align-items: start;
        }

        .reservation-show-kicker {
            margin: 0;
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: #2f6d9f;
            font-weight: 700;
        }

        .reservation-show-title {
            margin: 6px 0 6px;
            font-size: clamp(24px, 4vw, 34px);
            line-height: 1.05;
            color: var(--rs-text);
            font-weight: 800;
        }

        .reservation-show-sub {
            margin: 0;
            color: var(--rs-muted);
            font-size: 14px;
        }

        .reservation-show-chips {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .reservation-show-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .reservation-actions-menu {
            position: relative;
        }

        .reservation-actions-menu-panel {
            position: absolute;
            top: calc(100% + 6px);
            right: 0;
            min-width: 260px;
            background: #fff;
            border: 1px solid #dbe7f4;
            border-radius: 12px;
            box-shadow: 0 14px 28px rgba(20, 49, 77, 0.14);
            padding: 8px;
            display: none;
            z-index: 20;
        }

        .reservation-actions-menu-panel.show {
            display: grid;
            gap: 6px;
        }

        .reservation-actions-menu-item {
            width: 100%;
            text-align: left;
        }

        .reservation-chip {
            border-radius: 999px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
            border: 1px solid transparent;
            background: #ecf3fb;
            color: #285277;
        }

        .reservation-chip.ok { background: #e7f7ee; border-color: #bce8ce; color: #1d6a3d; }
        .reservation-chip.warn { background: #fff6e6; border-color: #f3deb1; color: #915c07; }
        .reservation-chip.danger { background: #ffeceb; border-color: #f7c4c1; color: #a9362f; }
        .reservation-chip.info { background: #eaf4ff; border-color: #c6def9; color: #1e5f9d; }

        .reservation-show-grid {
            display: grid;
            grid-template-columns: 1.3fr .9fr;
            gap: 14px;
        }

        .reservation-card {
            background: var(--rs-card);
            border: 1px solid var(--rs-line);
            border-radius: 16px;
            box-shadow: var(--rs-shadow);
            overflow: hidden;
        }

        .reservation-card-head {
            padding: 14px 16px;
            border-bottom: 1px solid var(--rs-line);
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .reservation-card-title {
            margin: 0;
            font-size: 15px;
            color: var(--rs-text);
            font-weight: 700;
        }

        .reservation-detail-list {
            margin: 0;
            padding: 10px 14px 14px;
            display: grid;
            gap: 8px;
        }

        .reservation-detail-row {
            border: 1px solid #e5edf7;
            border-radius: 12px;
            padding: 10px 12px;
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 10px;
            align-items: center;
            background: #fcfdff;
        }

        .reservation-detail-key {
            color: #48627d;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .reservation-detail-value {
            color: #163651;
            font-size: 14px;
            font-weight: 600;
            word-break: break-word;
        }

        .reservation-objects-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .reservation-object-head {
            padding: 14px 16px;
            border-bottom: 1px solid var(--rs-line);
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .reservation-object-title {
            margin: 0;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #3d5b78;
        }

        .reservation-switch {
            display: inline-flex;
            gap: 6px;
            padding: 4px;
            border-radius: 10px;
            border: 1px solid #d9e6f3;
            background: #f4f8fd;
        }

        .reservation-switch-btn {
            border: 1px solid transparent;
            background: transparent;
            color: #47627d;
            font-size: 12px;
            font-weight: 700;
            border-radius: 8px;
            padding: 7px 10px;
            cursor: pointer;
        }

        .reservation-switch-btn.is-active {
            background: #ffffff;
            color: #1f4f7a;
            border-color: #c6d9ee;
            box-shadow: 0 4px 10px rgba(20, 49, 77, 0.08);
        }

        .reservation-switch-panel {
            display: none;
        }

        .reservation-switch-panel.is-active {
            display: grid;
            gap: 8px;
        }

        .reservation-object-body {
            padding: 12px;
            display: grid;
            gap: 8px;
        }

        .reservation-kv {
            border: 1px solid #e5edf7;
            border-radius: 10px;
            padding: 9px 10px;
            background: #fcfdff;
            display: grid;
            gap: 3px;
        }

        .reservation-kv-key {
            color: #59728b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .04em;
            font-weight: 700;
        }

        .reservation-kv-value {
            color: #1a3a57;
            font-size: 14px;
            font-weight: 600;
        }

        .reservation-nearby {
            border: 1px solid #f2ddba;
            background: #fff8eb;
            border-radius: 10px;
            padding: 8px 9px;
            font-size: 12px;
            color: #8a5b0d;
            display: grid;
            gap: 4px;
        }

        .reservation-nearby strong {
            font-size: 12px;
        }

        .additional-service-category {
            border: 1px solid #dbe8f5;
            border-radius: 11px;
            background: #f8fbff;
            padding: 8px;
            display: grid;
            gap: 6px;
        }

        .additional-service-category h4 {
            margin: 0;
            font-size: 13px;
            color: #1f4970;
        }

        .additional-service-item {
            border: 1px solid #d7e4f2;
            border-radius: 9px;
            background: #fff;
            padding: 7px 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .additional-service-item small {
            color: #5f7690;
            display: block;
            margin-top: 2px;
        }

        .additional-service-item-right {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .reservation-actions-row {
            display: flex;
            justify-content: flex-end;
        }

        .payment-form {
            border: 1px dashed #c9dbef;
            border-radius: 12px;
            padding: 10px;
            background: #f8fbff;
            display: grid;
            gap: 8px;
        }

        .payment-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .payment-form label {
            font-size: 12px;
            color: #42617e;
            font-weight: 700;
            margin-bottom: 4px;
            display: inline-block;
        }

        .payment-form input,
        .payment-form select,
        .payment-form textarea {
            width: 100%;
            border: 1px solid #d3deea;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 13px;
            background: #fff;
            color: #183a5b;
        }

        .payment-form-help {
            margin: 0;
            font-size: 12px;
            color: #516b85;
            background: #eef5fd;
            border: 1px solid #d5e4f3;
            border-radius: 9px;
            padding: 8px;
        }

        .reservation-payment-list {
            margin: 0;
            padding: 12px;
            list-style: none;
            display: grid;
            gap: 10px;
        }

        .reservation-payment-item {
            border: 1px solid #e1ebf7;
            border-radius: 12px;
            padding: 10px 12px;
            background: #fbfdff;
            display: grid;
            gap: 6px;
        }

        .reservation-payment-row {
            display: grid;
            grid-template-columns: 40px 1fr;
            gap: 10px;
            align-items: center;
        }

        .reservation-payment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            background: #e8f1fb;
            border: 1px solid #c7dbf0;
            color: #1e4f7b;
            font-size: 12px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            letter-spacing: .04em;
        }

        .reservation-payment-main {
            min-width: 0;
            display: grid;
            gap: 4px;
        }

        .reservation-payment-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #244e76;
        }

        .reservation-payment-line strong {
            color: #143b5f;
        }

        .reservation-payment-sub {
            font-size: 12px;
            color: #4f6b86;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .reservation-payment-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .reservation-payment-id {
            font-weight: 700;
            color: #1c476d;
            font-size: 13px;
        }

        .reservation-payment-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #325774;
        }

        .reservation-empty {
            margin: 0;
            padding: 14px;
            color: #59738d;
            font-size: 13px;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(9, 15, 28, 0.55);
            z-index: 90;
            padding: 16px;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-card {
            width: min(620px, 100%);
            max-height: 90vh;
            overflow: auto;
            border-radius: 16px;
            border: 1px solid #d5e3f2;
            background: #fff;
            box-shadow: 0 20px 45px rgba(16, 35, 58, 0.20);
            padding: 14px;
            display: grid;
            gap: 10px;
        }

        .modal-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .modal-title {
            margin: 0;
            color: #173957;
            font-size: 18px;
        }

        .modal-card label {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
            font-weight: 700;
            color: #45617c;
        }

        .modal-card input:not([type="checkbox"]):not([type="radio"]),
        .modal-card select,
        .modal-card textarea {
            width: 100%;
            border: 1px solid #cddbeb;
            border-radius: 10px;
            padding: 9px 10px;
            font-size: 13px;
            background: #ffffff;
            color: #183a5b;
            transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
            appearance: none;
        }

        .modal-card input:not([type="checkbox"]):not([type="radio"]):focus,
        .modal-card select:focus,
        .modal-card textarea:focus {
            outline: none;
            border-color: #2c78b6;
            box-shadow: 0 0 0 3px rgba(44, 120, 182, 0.14);
            background: #fbfdff;
        }

        .modal-card input[readonly] {
            background: #eef3f8;
            color: #5b6b7d;
        }

        .client-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .client-form-grid .full {
            grid-column: 1 / -1;
        }

        .client-form-grid label {
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
            font-weight: 700;
            color: #45617c;
        }

        .client-form-grid input,
        .client-form-grid select,
        .client-form-grid textarea {
            width: 100%;
            border: 1px solid #d3deea;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 13px;
            background: #fff;
            color: #183a5b;
        }

        .client-form-grid input[readonly] {
            background: #eef3f8;
            color: #5b6b7d;
        }

        .salle-available-list {
            display: grid;
            gap: 8px;
        }

        .slot-inline-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr;
            gap: 8px;
            align-items: end;
        }

        .salle-card-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }

        .salle-card {
            border: 1px solid #d5e2f0;
            border-radius: 12px;
            padding: 10px;
            background: #fcfdff;
            cursor: pointer;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
            display: grid;
            gap: 5px;
        }

        .salle-card:hover {
            border-color: #9fc0e5;
            box-shadow: 0 6px 16px rgba(19, 59, 93, 0.10);
            transform: translateY(-1px);
        }

        .salle-card.is-selected {
            border-color: #2c78b6;
            box-shadow: 0 0 0 2px rgba(44, 120, 182, 0.18);
            background: #f2f8ff;
        }

        .salle-card-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            color: #1e4f7b;
            font-size: 13px;
        }

        .salle-card-meta {
            color: #607a95;
            font-size: 12px;
        }

        .salle-option {
            border: 1px solid #d5e2f0;
            border-radius: 10px;
            padding: 8px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            background: #fcfdff;
        }

        .salle-option-main {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .salle-color-dot {
            width: 11px;
            height: 11px;
            border-radius: 999px;
            border: 1px solid rgba(0, 0, 0, 0.2);
            display: inline-block;
        }

        .salle-option small {
            color: #607a95;
            font-size: 12px;
        }

        .staff-summary-grid {
            display: grid;
            gap: 8px;
        }

        .staff-summary-row {
            border: 1px solid #dbe7f4;
            border-radius: 10px;
            padding: 8px;
            background: #f8fbff;
            display: grid;
            gap: 4px;
        }

        .staff-assignment-preview-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .staff-assignment-preview-card {
            width: 58px;
            display: grid;
            justify-items: center;
            gap: 4px;
        }

        .staff-assignment-preview-avatar {
            width: 54px;
            height: 54px;
            border-radius: 999px;
            border: 2px solid #c8d9ea;
            background: #e9f1fb;
            color: #1d4d78;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(20, 49, 77, 0.08);
        }

        .staff-assignment-preview-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .staff-assignment-preview-name {
            max-width: 100%;
            font-size: 10px;
            line-height: 1.15;
            color: #5d7388;
            text-align: center;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }

        .staff-summary-label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #5a748f;
            font-weight: 700;
        }

        .staff-avatar-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
        }

        .staff-avatar-card {
            border: 1px solid #d5e2f0;
            border-radius: 12px;
            background: #fcfdff;
            padding: 8px;
            display: grid;
            justify-items: center;
            align-content: start;
            gap: 6px;
            cursor: pointer;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
            text-align: center;
        }

        .staff-avatar-card:hover {
            border-color: #9fc0e5;
            box-shadow: 0 6px 14px rgba(20, 49, 77, 0.10);
            transform: translateY(-1px);
        }

        .staff-avatar-card.is-disabled {
            opacity: 0.62;
            cursor: not-allowed;
            background: #f3f6fa;
        }

        .staff-avatar-card.is-disabled:hover {
            transform: none;
            border-color: #d5e2f0;
            box-shadow: none;
        }

        .staff-avatar-card input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .staff-avatar-card:has(input:checked) {
            border-color: #2c78b6;
            box-shadow: 0 0 0 2px rgba(44, 120, 182, 0.18);
            background: #f2f8ff;
        }

        .staff-avatar-card--busy {
            border-color: #d9aa00;
            box-shadow: 0 0 0 2px rgba(217, 170, 0, 0.22);
            background: #fffbea;
        }

        .staff-avatar-card--busy .staff-avatar {
            border-color: #d9aa00;
            background: #fff4c9;
        }

        .staff-avatar-card--busy:has(input:checked) {
            border-color: #c59300;
            box-shadow: 0 0 0 2px rgba(197, 147, 0, 0.28);
            background: #fff5cf;
        }

        .staff-avatar {
            width: 58px;
            height: 58px;
            border-radius: 999px;
            border: 1px solid #c7dbf0;
            background: #e8f1fb;
            color: #1e4f7b;
            font-size: 14px;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .staff-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .staff-avatar-name {
            font-size: 12px;
            color: #1a3a57;
            font-weight: 700;
            line-height: 1.2;
        }

        .staff-avatar-meta {
            font-size: 11px;
            color: #607a95;
            line-height: 1.2;
        }

        .staff-avatar-warning {
            font-size: 10px;
            font-weight: 700;
            color: #876200;
            background: #ffeeb2;
            border: 1px solid #e2bf44;
            border-radius: 999px;
            padding: 2px 7px;
            line-height: 1.2;
        }

        .staff-avatar-disabled-note {
            font-size: 10px;
            font-weight: 700;
            color: #6b7280;
            background: #e5e7eb;
            border: 1px solid #d1d5db;
            border-radius: 999px;
            padding: 2px 7px;
            line-height: 1.2;
        }

        @media (max-width: 960px) {
            .reservation-top-grid { grid-template-columns: 1fr; }
            .reservation-show-grid { grid-template-columns: 1fr; }
            .reservation-objects-grid { grid-template-columns: 1fr; }
            .payment-form-row { grid-template-columns: 1fr; }
            .client-form-grid { grid-template-columns: 1fr; }
            .slot-inline-grid { grid-template-columns: 1fr; }
            .salle-card-grid { grid-template-columns: 1fr; }
            .staff-avatar-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }

        @media (max-width: 640px) {
            .reservation-detail-row { grid-template-columns: 1fr; gap: 4px; }
            .staff-avatar-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>

    <section class="reservation-show">
        @if (session('success'))
            <div class="reservation-flash">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="reservation-error-box">
                <strong>Verifie les informations:</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="reservation-top-grid">
            <div class="reservation-show-hero">
                <div>
                    <p class="reservation-show-kicker">Reservation detail</p>
                    <h1 class="reservation-show-title">{{ $reservation->title ?: 'Reservation #' . $reservation->id }}</h1>
                    <p class="reservation-show-sub">Type: {{ $reservationTypeLabel }} ({{ $reservationScopeLabel }}) | Salle: {{ $reservation->salle?->name ?? '-' }} | Createur: {{ $reservation->user?->name ?? '-' }}</p>
                    <div class="reservation-show-chips">
                        <span class="reservation-chip {{ $statusTone }}">{{ $statusLabel }}</span>
                        <span class="reservation-chip">{{ $reservationScopeLabel }}</span>
                        <span class="reservation-chip">
                            @if ($reservation->start_date === $reservation->end_date)
                                @frDate($reservation->start_date)
                            @else
                                Du @frDate($reservation->start_date) au @frDate($reservation->end_date)
                            @endif
                        </span>
                        <span class="reservation-chip">{{ $reservation->start_time ?? '--:--' }} - {{ $reservation->end_time ?? '--:--' }}</span>
                        <span class="reservation-chip">&#127915; {{ $reservation->event_type ?: '-' }}</span>
                        <span class="reservation-chip">&#128101; {{ $reservation->guest_count ?: '-' }}</span>
                    </div>
                    <div class="reservation-show-actions" style="margin-top:10px;justify-content:flex-start;">
                        <a href="{{ route('reservations.contract', $reservation) }}" class="btn">Contrat</a>
                        <a href="{{ route('reservations.invoice', $reservation) }}" class="btn">Facture (TTC {{ number_format($invoiceTotalTtc, 3, '.', ' ') }} TND, TVA {{ (int) ($tvaRate * 100) }}% incluse)</a>
                    </div>
                </div>
                <div class="reservation-show-actions">
                    @if ($canUpdateReservation && ($reservation->status ?? null) !== 'cancelled')
                        <div class="reservation-actions-menu" id="reservation-actions-menu">
                            <button type="button" class="btn btn-primary" id="reservation-actions-toggle">Actions reservation</button>
                            <div class="reservation-actions-menu-panel" id="reservation-actions-panel">
                                @if ($canCreateReservation)
                                    <button type="button" class="btn reservation-actions-menu-item" data-open-modal="clone-reservation-modal">Cloner reservation</button>
                                @endif
                                <button type="button" class="btn reservation-actions-menu-item" data-open-modal="reservation-modal">Modifier reservation</button>
                                <button type="button" class="btn reservation-actions-menu-item" data-open-modal="reservation-slot-modal">Modifier date/heure/salle</button>
                                <button type="button" class="btn reservation-actions-menu-item" data-open-modal="cancel-reservation-modal" style="border-color:#efc1bf;color:#a9362f;background:#fff3f2;">Annuler la reservation</button>
                            </div>
                        </div>
                    @endif
                    @if ($canCreateReservation && ($reservation->status ?? null) === 'cancelled')
                        <button type="button" class="btn" data-open-modal="clone-reservation-modal">Cloner reservation</button>
                    @endif
                    <a href="{{ route('reservations.index') }}" class="btn">Retour au calendrier</a>
                </div>
            </div>

            <article class="reservation-card">
                <div class="reservation-object-head">
                    <div class="reservation-switch" id="reservation-summary-switch">
                        <button type="button" class="reservation-switch-btn is-active" data-switch-target="staff">Affectation staff event</button>
                        <button type="button" class="reservation-switch-btn" data-switch-target="client">Informations client</button>
                    </div>
                </div>
                <div class="reservation-object-body">
                    <div class="reservation-switch-panel is-active" data-switch-panel="staff">
                        @if (! $isSalleReservation)
                            <p class="reservation-empty">L affectation staff est disponible uniquement pour les reservations de type salle.</p>
                        @else
                            <div class="staff-summary-grid">
                            <div class="staff-summary-row">
                                <span class="staff-summary-label">Chef de service</span>
                                @if ($gerantAffectation && ($staffByUserId->get($gerantAffectation->user_id) ?? null))
                                    @php
                                        $gerantStaff = $staffByUserId->get($gerantAffectation->user_id);
                                        $gerantLabel = trim((string) ($gerantStaff->full_name ?: ($gerantAffectation->user?->name ?? '')));
                                        $gerantInitials = '';
                                        foreach (preg_split('/\s+/', $gerantLabel) ?: [] as $part) {
                                            if ($part !== '') {
                                                $gerantInitials .= mb_strtoupper(mb_substr($part, 0, 1));
                                            }
                                            if (mb_strlen($gerantInitials) >= 2) {
                                                break;
                                            }
                                        }
                                        if ($gerantInitials === '') {
                                            $gerantInitials = 'ST';
                                        }
                                    @endphp
                                    <div class="staff-assignment-preview-grid">
                                        <div class="staff-assignment-preview-card" title="{{ $gerantLabel }}">
                                            <span class="staff-assignment-preview-avatar">
                                                @if (!empty($gerantStaff->photo_path))
                                                    <img src="{{ asset('storage/' . ltrim($gerantStaff->photo_path, '/')) }}" alt="{{ $gerantLabel }}">
                                                @else
                                                    {{ $gerantInitials }}
                                                @endif
                                            </span>
                                            <span class="staff-assignment-preview-name">{{ $gerantLabel }}</span>
                                        </div>
                                    </div>
                                @else
                                    <strong>Aucun</strong>
                                @endif
                            </div>
                            <div class="staff-summary-row">
                                <span class="staff-summary-label">Serveur</span>
                                @if ($serveurAffectations->isNotEmpty())
                                    <div class="staff-assignment-preview-grid">
                                        @foreach ($serveurAffectations as $row)
                                            @php
                                                $staffRow = $staffByUserId->get($row->user_id);
                                                $staffLabel = trim((string) ($staffRow->full_name ?? ($row->user?->name ?? '')));
                                                $staffLabel = $staffLabel !== '' ? $staffLabel : ('Utilisateur #' . $row->user_id);
                                                $initials = '';
                                                foreach (preg_split('/\s+/', $staffLabel) ?: [] as $part) {
                                                    if ($part !== '') {
                                                        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                                                    }
                                                    if (mb_strlen($initials) >= 2) {
                                                        break;
                                                    }
                                                }
                                                if ($initials === '') {
                                                    $initials = 'ST';
                                                }
                                            @endphp
                                            <div class="staff-assignment-preview-card" title="{{ $staffLabel }}">
                                                <span class="staff-assignment-preview-avatar">
                                                    @if ($staffRow && !empty($staffRow->photo_path))
                                                        <img src="{{ asset('storage/' . ltrim($staffRow->photo_path, '/')) }}" alt="{{ $staffLabel }}">
                                                    @else
                                                        {{ $initials }}
                                                    @endif
                                                </span>
                                                <span class="staff-assignment-preview-name">{{ $staffLabel }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <strong>Aucun</strong>
                                @endif
                            </div>
                            <div class="staff-summary-row">
                                <span class="staff-summary-label">Annimateur</span>
                                @if ($annimateurAffectations->isNotEmpty())
                                    <div class="staff-assignment-preview-grid">
                                        @foreach ($annimateurAffectations as $row)
                                            @php
                                                $staffRow = $staffByUserId->get($row->user_id);
                                                $staffLabel = trim((string) ($staffRow->full_name ?? ($row->user?->name ?? '')));
                                                $staffLabel = $staffLabel !== '' ? $staffLabel : ('Utilisateur #' . $row->user_id);
                                                $initials = '';
                                                foreach (preg_split('/\s+/', $staffLabel) ?: [] as $part) {
                                                    if ($part !== '') {
                                                        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                                                    }
                                                    if (mb_strlen($initials) >= 2) {
                                                        break;
                                                    }
                                                }
                                                if ($initials === '') {
                                                    $initials = 'ST';
                                                }
                                            @endphp
                                            <div class="staff-assignment-preview-card" title="{{ $staffLabel }}">
                                                <span class="staff-assignment-preview-avatar">
                                                    @if ($staffRow && !empty($staffRow->photo_path))
                                                        <img src="{{ asset('storage/' . ltrim($staffRow->photo_path, '/')) }}" alt="{{ $staffLabel }}">
                                                    @else
                                                        {{ $initials }}
                                                    @endif
                                                </span>
                                                <span class="staff-assignment-preview-name">{{ $staffLabel }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <strong>Aucun</strong>
                                @endif
                            </div>
                            <div class="staff-summary-row">
                                <span class="staff-summary-label">Femme de menage</span>
                                @if ($femmeMenageAffectations->isNotEmpty())
                                    <div class="staff-assignment-preview-grid">
                                        @foreach ($femmeMenageAffectations as $row)
                                            @php
                                                $staffRow = $staffByUserId->get($row->user_id);
                                                $staffLabel = trim((string) ($staffRow->full_name ?? ($row->user?->name ?? '')));
                                                $staffLabel = $staffLabel !== '' ? $staffLabel : ('Utilisateur #' . $row->user_id);
                                                $initials = '';
                                                foreach (preg_split('/\s+/', $staffLabel) ?: [] as $part) {
                                                    if ($part !== '') {
                                                        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                                                    }
                                                    if (mb_strlen($initials) >= 2) {
                                                        break;
                                                    }
                                                }
                                                if ($initials === '') {
                                                    $initials = 'ST';
                                                }
                                            @endphp
                                            <div class="staff-assignment-preview-card" title="{{ $staffLabel }}">
                                                <span class="staff-assignment-preview-avatar">
                                                    @if ($staffRow && !empty($staffRow->photo_path))
                                                        <img src="{{ asset('storage/' . ltrim($staffRow->photo_path, '/')) }}" alt="{{ $staffLabel }}">
                                                    @else
                                                        {{ $initials }}
                                                    @endif
                                                </span>
                                                <span class="staff-assignment-preview-name">{{ $staffLabel }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <strong>Aucune</strong>
                                @endif
                            </div>
                            <div class="staff-summary-row">
                                <span class="staff-summary-label">Agent de securite</span>
                                @if ($agentSecuriteAffectations->isNotEmpty())
                                    <div class="staff-assignment-preview-grid">
                                        @foreach ($agentSecuriteAffectations as $row)
                                            @php
                                                $staffRow = $staffByUserId->get($row->user_id);
                                                $staffLabel = trim((string) ($staffRow->full_name ?? ($row->user?->name ?? '')));
                                                $staffLabel = $staffLabel !== '' ? $staffLabel : ('Utilisateur #' . $row->user_id);
                                                $initials = '';
                                                foreach (preg_split('/\s+/', $staffLabel) ?: [] as $part) {
                                                    if ($part !== '') {
                                                        $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                                                    }
                                                    if (mb_strlen($initials) >= 2) {
                                                        break;
                                                    }
                                                }
                                                if ($initials === '') {
                                                    $initials = 'ST';
                                                }
                                            @endphp
                                            <div class="staff-assignment-preview-card" title="{{ $staffLabel }}">
                                                <span class="staff-assignment-preview-avatar">
                                                    @if ($staffRow && !empty($staffRow->photo_path))
                                                        <img src="{{ asset('storage/' . ltrim($staffRow->photo_path, '/')) }}" alt="{{ $staffLabel }}">
                                                    @else
                                                        {{ $initials }}
                                                    @endif
                                                </span>
                                                <span class="staff-assignment-preview-name">{{ $staffLabel }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <strong>Aucun</strong>
                                @endif
                            </div>
                            </div>

                            @if ($canUpdateReservation)
                                <div class="reservation-actions-row" style="justify-content:flex-start; margin-top:8px;">
                                    <button type="button" class="btn btn-primary" data-open-modal="staff-affectation-modal">Modifier affectation staff</button>
                                </div>
                            @endif
                        @endif
                    </div>

                    <div class="reservation-switch-panel" data-switch-panel="client">
                        <div class="reservation-kv"><span class="reservation-kv-key">Nom complet</span><span class="reservation-kv-value">{{ $clientFullName }}</span></div>
                        <div class="reservation-kv"><span class="reservation-kv-key">CIN</span><span class="reservation-kv-value">{{ $reservation->client?->cin ?? '-' }}</span></div>
                        <div class="reservation-kv"><span class="reservation-kv-key">Mobile 1</span><span class="reservation-kv-value">{{ $reservation->client?->phone ?? '-' }}{{ $reservation->client?->phone_label_1 ? ' (' . $reservation->client->phone_label_1 . ')' : '' }}</span></div>
                        <div class="reservation-kv"><span class="reservation-kv-key">Mobile 2</span><span class="reservation-kv-value">{{ $reservation->client?->phone_2 ?? '-' }}{{ $reservation->client?->phone_label_2 ? ' (' . $reservation->client->phone_label_2 . ')' : '' }}</span></div>
                        <div class="reservation-kv"><span class="reservation-kv-key">Adresse</span><span class="reservation-kv-value">{{ $clientAddress }}</span></div>
                        @if ($canUpdateReservation)
                            <div class="reservation-actions-row" style="justify-content:flex-start; margin-top:8px;">
                                <button type="button" class="btn" data-open-modal="client-modal">Modifier donnees client</button>
                            </div>
                        @endif
                    </div>
                </div>
            </article>
        </div>

        <div class="reservation-objects-grid">
            <article class="reservation-card">
                <div class="reservation-object-head">
                    <h3 class="reservation-object-title">Services supplementaires</h3>
                    @if ($canUpdateReservation && $isSalleReservation)
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <button type="button" class="btn" data-open-modal="reservation-salle-option-modal">Ajouter option salle</button>
                            <button type="button" class="btn" data-open-modal="additional-service-modal">Ajouter service</button>
                        </div>
                    @endif
                </div>
                <div class="reservation-object-body">
                    @if (($nearbyCreneaux ?? collect())->isNotEmpty())
                        <div class="reservation-nearby">
                            <strong>Reservations proches (+/- 1h30)</strong>
                            @foreach ($nearbyCreneaux as $nearby)
                                <span>
                                    {{ $nearby['position'] === 'before' ? 'Avant' : 'Apres' }} ({{ $nearby['gap_minutes'] }} min):
                                    {{ $nearby['title'] }}
                                    @if (! empty($nearby['client']))
                                        - {{ $nearby['client'] }}
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if (! $isSalleReservation)
                        <p class="reservation-empty">Les services supplementaires sont disponibles uniquement pour les reservations de type salle.</p>
                    @else
                        <div class="additional-service-category">
                            <h4>Options de la salle</h4>
                            @if ($reservation->salleOptionRows->isEmpty())
                                <p class="reservation-empty">Aucune option salle liee a cette reservation.</p>
                            @else
                                @foreach ($reservation->salleOptionRows as $optionRow)
                                    <div class="additional-service-item">
                                        <div>
                                            <strong>{{ $optionRow->label }}</strong>
                                            <small>{{ number_format((float) $optionRow->amount, 2, '.', ' ') }} @if(!empty($optionRow->note)) - {{ $optionRow->note }} @endif</small>
                                        </div>
                                        @if ($canUpdateReservation)
                                            <form method="POST" action="{{ route('reservations.salle-options.destroy', [$reservation, $optionRow]) }}" onsubmit="return confirm('Retirer cette option de la reservation ?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn">Retirer</button>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        @foreach ($additionalServiceModules as $moduleSlug => $moduleLabel)
                            @php
                                $rows = $additionalServicesByCategory[$moduleSlug]['rows'] ?? collect();
                            @endphp
                            @if (! $rows->isEmpty())
                                <div class="additional-service-category">
                                    <h4>{{ $moduleLabel }}</h4>
                                    @foreach ($rows as $row)
                                        <div class="additional-service-item">
                                            <div>
                                                <strong>{{ $row->label }}</strong>
                                                <small>{{ number_format((float) $row->amount, 2, '.', ' ') }} @if(!empty($row->note)) - {{ $row->note }} @endif</small>
                                                @if ($row->linkedReservation)
                                                    @php
                                                        $linkedTotalPaid = $row->linkedReservation->payments->sum('amount');
                                                        $linkedReste = (float) $row->amount - $linkedTotalPaid;
                                                    @endphp
                                                    <small>Reste a payer: <strong>{{ number_format($linkedReste, 2, '.', ' ') }}</strong></small>
                                                @endif
                                            </div>
                                            @if ($canUpdateReservation)
                                                <div class="additional-service-item-right" style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                                                    @if ($row->linkedReservation)
                                                        <a href="{{ route('reservations.show', $row->linkedReservation) }}" class="btn">Afficher</a>
                                                    @else
                                                        <button type="button" class="btn" disabled>Afficher</button>
                                                    @endif
                                                    @if ($row->linkedReservation)
                                                        <div style="display:flex;align-items:center;gap:4px;">
                                                            <label style="font-size:0.8em;white-space:nowrap;">Heure:</label>
                                                            <input type="time"
                                                                value="{{ $row->linkedReservation->start_time ? \Carbon\Carbon::parse($row->linkedReservation->start_time)->format('H:i') : '' }}"
                                                                style="font-size:0.8em;padding:2px 4px;width:95px;"
                                                                data-url="{{ route('reservations.additional-services.start-time.update', [$reservation, $row]) }}"
                                                                data-token="{{ csrf_token() }}"
                                                                class="js-service-start-time">
                                                            <span class="js-start-time-status" style="font-size:0.75em;"></span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
            </article>

            <article class="reservation-card">
                <div class="reservation-object-head">
                    <h3 class="reservation-object-title">Notes satisfaction client</h3>
                </div>
                <div class="reservation-object-body">
                    @if ($reservation->serviceFeedbacks->isEmpty())
                        <p class="reservation-empty">Aucune note de satisfaction enregistree.</p>
                    @else
                        @foreach ($reservation->serviceFeedbacks->sortByDesc('created_dtm') as $feedback)
                            <div class="reservation-kv">
                                <span class="reservation-kv-key">{{ $feedback->nom ?: ($feedback->creator?->name ?? 'Client') }} - @frDateTime($feedback->created_dtm)</span>
                                <span class="reservation-kv-value">
                                    Salle: {{ $feedback->note_salle ?? '-' }} / 10 | Service: {{ $feedback->note_service ?? '-' }} / 10<br>
                                    {{ $feedback->commentaire ?: 'Sans commentaire.' }}
                                </span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </article>

            <article class="reservation-card">
                <div class="reservation-object-head">
                    <h3 class="reservation-object-title">Paiements</h3>
                    <div style="display:flex;gap:6px;flex-wrap:wrap;">
                        @if ($canCreatePayment)
                            <button type="button" class="btn btn-primary" data-open-modal="payment-modal" {{ $remainingAmount <= 0 ? 'disabled' : '' }}>Ajouter paiement</button>
                        @endif
                    </div>
                </div>

                <div class="reservation-object-body">
                    <div class="reservation-kv"><span class="reservation-kv-key">Total reservation</span><span class="reservation-kv-value">{{ number_format($totalAmount, 2, '.', ' ') }}</span></div>
                    <div class="reservation-kv"><span class="reservation-kv-key">Total paye</span><span class="reservation-kv-value">{{ number_format($totalPaid, 2, '.', ' ') }}</span></div>
                    <div class="reservation-kv"><span class="reservation-kv-key">Reste</span><span class="reservation-kv-value">{{ number_format($remainingAmount, 2, '.', ' ') }}</span></div>
                    <div class="reservation-kv"><span class="reservation-kv-key">Solde client (avoir - {{ $creditServiceLabel }})</span><span class="reservation-kv-value">{{ number_format($clientCreditBalance, 2, '.', ' ') }}</span></div>

                    @if ($reservation->payments->isEmpty())
                        <p class="reservation-empty">Aucun paiement lie a cette reservation.</p>
                    @else
                        <ul class="reservation-payment-list">
                            @foreach ($reservation->payments as $payment)
                                @php
                                    $receiverName = trim((string) ($payment->user?->name ?? 'Recepteur'));
                                    $receiverParts = preg_split('/\s+/', $receiverName) ?: [];
                                    $receiverInitials = '';
                                    foreach ($receiverParts as $part) {
                                        if ($part !== '') {
                                            $receiverInitials .= mb_strtoupper(mb_substr($part, 0, 1));
                                        }
                                        if (mb_strlen($receiverInitials) >= 2) {
                                            break;
                                        }
                                    }
                                    if ($receiverInitials === '') {
                                        $receiverInitials = 'R';
                                    }
                                @endphp
                                <li class="reservation-payment-item">
                                    <div class="reservation-payment-row">
                                        <div class="reservation-payment-avatar">{{ $receiverInitials }}</div>
                                        <div class="reservation-payment-main">
                                            <div class="reservation-payment-line">
                                                <span>{{ $payment->reference ?: ('Paiement #' . $payment->id) }}</span>
                                                <strong>{{ number_format((float) ($payment->amount ?? 0), 2, '.', ' ') }}</strong>
                                            </div>
                                            <div class="reservation-payment-sub">@frDateTime($payment->paid_at) • {{ $payment->method ?? '-' }}</div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </article>

        </div>
    </section>

    @if ($canUpdateReservation)
        @if ($isSalleReservation)
            <div class="modal-overlay" id="staff-affectation-modal">
                <div class="modal-card" style="width:min(980px,100%);">
                    <div class="modal-head">
                        <h3 class="modal-title">Affectation staff event</h3>
                        <button type="button" class="btn" data-close-modal>Fermer</button>
                    </div>

                    <form method="POST" action="{{ route('reservations.staff-affectations.update', $reservation) }}" style="display:grid;gap:12px;">
                        @csrf
                        @method('PATCH')

                        <p class="payment-form-help">Selectionne les membres par section. Filtre possible par departement. Tri alphabetique applique.</p>

                        @php
                            $departments = $staffOptions
                                ->map(function ($staffOption) {
                                    return [
                                        'id' => (string) ($staffOption->department_id ?? ''),
                                        'name' => (string) ($staffOption->department?->name ?? 'Sans departement'),
                                    ];
                                })
                                ->unique('id')
                                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                                ->values();

                            $sortedStaffOptions = $staffOptions
                                ->sortBy(function ($staffOption) {
                                    return mb_strtolower((string) ($staffOption->full_name ?: ($staffOption->user?->name ?? '')));
                                }, SORT_NATURAL | SORT_FLAG_CASE)
                                ->values();

                            $oldManagerUserId = old('manager_staff_user_id', $gerantAffectation?->user_id);
                            $oldServeurs = collect(old('serveur_staff_user_ids', $selectedServeurUserIds->all()))->map(fn ($id) => (string) $id);
                            $oldAnnimateurs = collect(old('annimateur_staff_user_ids', $selectedAnnimateurUserIds->all()))->map(fn ($id) => (string) $id);
                            $oldFemmesMenage = collect(old('femme_menage_staff_user_ids', $selectedFemmeMenageUserIds->all()))->map(fn ($id) => (string) $id);
                            $oldAgentsSecurite = collect(old('agent_securite_staff_user_ids', $selectedAgentSecuriteUserIds->all()))->map(fn ($id) => (string) $id);
                        @endphp

                        <div>
                            <label for="staff-department-filter">Filtre departement</label>
                            <select id="staff-department-filter">
                                <option value="">Tous les departements</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department['id'] }}">{{ $department['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="additional-service-category">
                            <h4>Section Chef de service</h4>
                            <div class="staff-avatar-grid">
                                <label class="staff-avatar-card" data-department-id="" data-role="gerant">
                                    <input type="radio" name="manager_staff_user_id" value="" {{ (string) $oldManagerUserId === '' ? 'checked' : '' }}>
                                    <span class="staff-avatar">-</span>
                                    <span class="staff-avatar-name">Aucun</span>
                                    <span class="staff-avatar-meta">Affectation vide</span>
                                </label>
                                @foreach ($sortedStaffOptions as $staffOption)
                                    @php
                                        if ((string) ($staffOption->reservation_section_slug ?? '') !== 'chef-service') {
                                            continue;
                                        }
                                        $staffUserId = (int) ($staffOption->user_id ?? 0);
                                        $isSelectable = $staffUserId > 0 && $staffOption->user !== null;
                                        $isBusyOnParallelReservation = $parallelBusyStaffUserIds->contains($staffUserId);
                                        $staffLabel = trim((string) (($staffOption->full_name ?? '') !== '' ? $staffOption->full_name : ($staffOption->user?->name ?? '')));
                                        $staffLabel = $staffLabel !== '' ? $staffLabel : ('Staff #' . $staffOption->id);
                                        $departmentId = (string) ($staffOption->department_id ?? '');
                                        $departmentName = (string) ($staffOption->department?->name ?? 'Sans departement');
                                        $initials = '';
                                        foreach (preg_split('/\s+/', $staffLabel) ?: [] as $part) {
                                            if ($part !== '') {
                                                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                                            }
                                            if (mb_strlen($initials) >= 2) {
                                                break;
                                            }
                                        }
                                        if ($initials === '') {
                                            $initials = 'ST';
                                        }
                                    @endphp
                                    <label class="staff-avatar-card {{ $isBusyOnParallelReservation ? 'staff-avatar-card--busy' : '' }} {{ $isSelectable ? '' : 'is-disabled' }}" data-department-id="{{ $departmentId }}" data-role="gerant" title="{{ ! $isSelectable ? 'Compte utilisateur non lie a cette fiche RH.' : ($isBusyOnParallelReservation ? 'Deja affecte sur une reservation en parallele.' : '') }}">
                                        <input type="radio" name="manager_staff_user_id" value="{{ $staffUserId }}" {{ (string) $oldManagerUserId === (string) $staffUserId ? 'checked' : '' }} {{ $isSelectable ? '' : 'disabled' }}>
                                        <span class="staff-avatar">
                                            @if (!empty($staffOption->photo_path))
                                                <img src="{{ asset('storage/' . ltrim($staffOption->photo_path, '/')) }}" alt="{{ $staffLabel }}">
                                            @else
                                                {{ $initials }}
                                            @endif
                                        </span>
                                        <span class="staff-avatar-name">{{ $staffLabel }}</span>
                                        <span class="staff-avatar-meta">{{ $departmentName }}</span>
                                        @if ($isBusyOnParallelReservation)
                                            <span class="staff-avatar-warning">Occupe</span>
                                        @endif
                                        @if (! $isSelectable)
                                            <span class="staff-avatar-disabled-note">Compte non lie</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="additional-service-category">
                            <h4>Section Serveur</h4>
                            <div class="staff-avatar-grid">
                                @foreach ($sortedStaffOptions as $staffOption)
                                    @php
                                        if ((string) ($staffOption->reservation_section_slug ?? '') !== 'serveur') {
                                            continue;
                                        }
                                        $staffUserId = (int) ($staffOption->user_id ?? 0);
                                        $isSelectable = $staffUserId > 0 && $staffOption->user !== null;
                                        $isBusyOnParallelReservation = $parallelBusyStaffUserIds->contains($staffUserId);
                                        $staffLabel = trim((string) (($staffOption->full_name ?? '') !== '' ? $staffOption->full_name : ($staffOption->user?->name ?? '')));
                                        $staffLabel = $staffLabel !== '' ? $staffLabel : ('Staff #' . $staffOption->id);
                                        $departmentId = (string) ($staffOption->department_id ?? '');
                                        $departmentName = (string) ($staffOption->department?->name ?? 'Sans departement');
                                        $initials = '';
                                        foreach (preg_split('/\s+/', $staffLabel) ?: [] as $part) {
                                            if ($part !== '') {
                                                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                                            }
                                            if (mb_strlen($initials) >= 2) {
                                                break;
                                            }
                                        }
                                        if ($initials === '') {
                                            $initials = 'ST';
                                        }
                                    @endphp
                                    <label class="staff-avatar-card {{ $isBusyOnParallelReservation ? 'staff-avatar-card--busy' : '' }} {{ $isSelectable ? '' : 'is-disabled' }}" data-department-id="{{ $departmentId }}" data-role="serveur" title="{{ ! $isSelectable ? 'Compte utilisateur non lie a cette fiche RH.' : ($isBusyOnParallelReservation ? 'Deja affecte sur une reservation en parallele.' : '') }}">
                                        <input type="checkbox" name="serveur_staff_user_ids[]" value="{{ $staffUserId }}" {{ $oldServeurs->contains((string) $staffUserId) ? 'checked' : '' }} {{ $isSelectable ? '' : 'disabled' }}>
                                        <span class="staff-avatar">
                                            @if (!empty($staffOption->photo_path))
                                                <img src="{{ asset('storage/' . ltrim($staffOption->photo_path, '/')) }}" alt="{{ $staffLabel }}">
                                            @else
                                                {{ $initials }}
                                            @endif
                                        </span>
                                        <span class="staff-avatar-name">{{ $staffLabel }}</span>
                                        <span class="staff-avatar-meta">{{ $departmentName }}</span>
                                        @if ($isBusyOnParallelReservation)
                                            <span class="staff-avatar-warning">Occupe</span>
                                        @endif
                                        @if (! $isSelectable)
                                            <span class="staff-avatar-disabled-note">Compte non lie</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="additional-service-category">
                            <h4>Section Annimateur</h4>
                            <div class="staff-avatar-grid">
                                @foreach ($sortedStaffOptions as $staffOption)
                                    @php
                                        if ((string) ($staffOption->reservation_section_slug ?? '') !== 'annimateur') {
                                            continue;
                                        }
                                        $staffUserId = (int) ($staffOption->user_id ?? 0);
                                        $isSelectable = $staffUserId > 0 && $staffOption->user !== null;
                                        $isBusyOnParallelReservation = $parallelBusyStaffUserIds->contains($staffUserId);
                                        $staffLabel = trim((string) (($staffOption->full_name ?? '') !== '' ? $staffOption->full_name : ($staffOption->user?->name ?? '')));
                                        $staffLabel = $staffLabel !== '' ? $staffLabel : ('Staff #' . $staffOption->id);
                                        $departmentId = (string) ($staffOption->department_id ?? '');
                                        $departmentName = (string) ($staffOption->department?->name ?? 'Sans departement');
                                        $initials = '';
                                        foreach (preg_split('/\s+/', $staffLabel) ?: [] as $part) {
                                            if ($part !== '') {
                                                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                                            }
                                            if (mb_strlen($initials) >= 2) {
                                                break;
                                            }
                                        }
                                        if ($initials === '') {
                                            $initials = 'ST';
                                        }
                                    @endphp
                                    <label class="staff-avatar-card {{ $isBusyOnParallelReservation ? 'staff-avatar-card--busy' : '' }} {{ $isSelectable ? '' : 'is-disabled' }}" data-department-id="{{ $departmentId }}" data-role="annimateur" title="{{ ! $isSelectable ? 'Compte utilisateur non lie a cette fiche RH.' : ($isBusyOnParallelReservation ? 'Deja affecte sur une reservation en parallele.' : '') }}">
                                        <input type="checkbox" name="annimateur_staff_user_ids[]" value="{{ $staffUserId }}" {{ $oldAnnimateurs->contains((string) $staffUserId) ? 'checked' : '' }} {{ $isSelectable ? '' : 'disabled' }}>
                                        <span class="staff-avatar">
                                            @if (!empty($staffOption->photo_path))
                                                <img src="{{ asset('storage/' . ltrim($staffOption->photo_path, '/')) }}" alt="{{ $staffLabel }}">
                                            @else
                                                {{ $initials }}
                                            @endif
                                        </span>
                                        <span class="staff-avatar-name">{{ $staffLabel }}</span>
                                        <span class="staff-avatar-meta">{{ $departmentName }}</span>
                                        @if ($isBusyOnParallelReservation)
                                            <span class="staff-avatar-warning">Occupe</span>
                                        @endif
                                        @if (! $isSelectable)
                                            <span class="staff-avatar-disabled-note">Compte non lie</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="additional-service-category">
                            <h4>Section Femme de menage</h4>
                            <div class="staff-avatar-grid">
                                @foreach ($sortedStaffOptions as $staffOption)
                                    @php
                                        if ((string) ($staffOption->reservation_section_slug ?? '') !== 'femme-menage') {
                                            continue;
                                        }
                                        $staffUserId = (int) ($staffOption->user_id ?? 0);
                                        $isSelectable = $staffUserId > 0 && $staffOption->user !== null;
                                        $isBusyOnParallelReservation = $parallelBusyStaffUserIds->contains($staffUserId);
                                        $staffLabel = trim((string) (($staffOption->full_name ?? '') !== '' ? $staffOption->full_name : ($staffOption->user?->name ?? '')));
                                        $staffLabel = $staffLabel !== '' ? $staffLabel : ('Staff #' . $staffOption->id);
                                        $departmentId = (string) ($staffOption->department_id ?? '');
                                        $departmentName = (string) ($staffOption->department?->name ?? 'Sans departement');
                                        $initials = '';
                                        foreach (preg_split('/\s+/', $staffLabel) ?: [] as $part) {
                                            if ($part !== '') {
                                                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                                            }
                                            if (mb_strlen($initials) >= 2) {
                                                break;
                                            }
                                        }
                                        if ($initials === '') {
                                            $initials = 'ST';
                                        }
                                    @endphp
                                    <label class="staff-avatar-card {{ $isBusyOnParallelReservation ? 'staff-avatar-card--busy' : '' }} {{ $isSelectable ? '' : 'is-disabled' }}" data-department-id="{{ $departmentId }}" data-role="femme-menage" title="{{ ! $isSelectable ? 'Compte utilisateur non lie a cette fiche RH.' : ($isBusyOnParallelReservation ? 'Deja affecte sur une reservation en parallele.' : '') }}">
                                        <input type="checkbox" name="femme_menage_staff_user_ids[]" value="{{ $staffUserId }}" {{ $oldFemmesMenage->contains((string) $staffUserId) ? 'checked' : '' }} {{ $isSelectable ? '' : 'disabled' }}>
                                        <span class="staff-avatar">
                                            @if (!empty($staffOption->photo_path))
                                                <img src="{{ asset('storage/' . ltrim($staffOption->photo_path, '/')) }}" alt="{{ $staffLabel }}">
                                            @else
                                                {{ $initials }}
                                            @endif
                                        </span>
                                        <span class="staff-avatar-name">{{ $staffLabel }}</span>
                                        <span class="staff-avatar-meta">{{ $departmentName }}</span>
                                        @if ($isBusyOnParallelReservation)
                                            <span class="staff-avatar-warning">Occupe</span>
                                        @endif
                                        @if (! $isSelectable)
                                            <span class="staff-avatar-disabled-note">Compte non lie</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="additional-service-category">
                            <h4>Section Agent de securite</h4>
                            <div class="staff-avatar-grid">
                                @foreach ($sortedStaffOptions as $staffOption)
                                    @php
                                        if ((string) ($staffOption->reservation_section_slug ?? '') !== 'agent-securite') {
                                            continue;
                                        }
                                        $staffUserId = (int) ($staffOption->user_id ?? 0);
                                        $isSelectable = $staffUserId > 0 && $staffOption->user !== null;
                                        $isBusyOnParallelReservation = $parallelBusyStaffUserIds->contains($staffUserId);
                                        $staffLabel = trim((string) (($staffOption->full_name ?? '') !== '' ? $staffOption->full_name : ($staffOption->user?->name ?? '')));
                                        $staffLabel = $staffLabel !== '' ? $staffLabel : ('Staff #' . $staffOption->id);
                                        $departmentId = (string) ($staffOption->department_id ?? '');
                                        $departmentName = (string) ($staffOption->department?->name ?? 'Sans departement');
                                        $initials = '';
                                        foreach (preg_split('/\s+/', $staffLabel) ?: [] as $part) {
                                            if ($part !== '') {
                                                $initials .= mb_strtoupper(mb_substr($part, 0, 1));
                                            }
                                            if (mb_strlen($initials) >= 2) {
                                                break;
                                            }
                                        }
                                        if ($initials === '') {
                                            $initials = 'ST';
                                        }
                                    @endphp
                                    <label class="staff-avatar-card {{ $isBusyOnParallelReservation ? 'staff-avatar-card--busy' : '' }} {{ $isSelectable ? '' : 'is-disabled' }}" data-department-id="{{ $departmentId }}" data-role="agent-securite" title="{{ ! $isSelectable ? 'Compte utilisateur non lie a cette fiche RH.' : ($isBusyOnParallelReservation ? 'Deja affecte sur une reservation en parallele.' : '') }}">
                                        <input type="checkbox" name="agent_securite_staff_user_ids[]" value="{{ $staffUserId }}" {{ $oldAgentsSecurite->contains((string) $staffUserId) ? 'checked' : '' }} {{ $isSelectable ? '' : 'disabled' }}>
                                        <span class="staff-avatar">
                                            @if (!empty($staffOption->photo_path))
                                                <img src="{{ asset('storage/' . ltrim($staffOption->photo_path, '/')) }}" alt="{{ $staffLabel }}">
                                            @else
                                                {{ $initials }}
                                            @endif
                                        </span>
                                        <span class="staff-avatar-name">{{ $staffLabel }}</span>
                                        <span class="staff-avatar-meta">{{ $departmentName }}</span>
                                        @if ($isBusyOnParallelReservation)
                                            <span class="staff-avatar-warning">Occupe</span>
                                        @endif
                                        @if (! $isSelectable)
                                            <span class="staff-avatar-disabled-note">Compte non lie</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="reservation-actions-row">
                            <button type="submit" class="btn btn-primary">Enregistrer affectation staff</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="modal-overlay" id="cancel-reservation-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Annuler la reservation</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>

                <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" style="display:grid; gap:10px;">
                    @csrf
                    @method('PATCH')

                    <p class="payment-form-help">L annulation exige la presence du client sur site, la signature du contrat de resiliation, et la creation d un avoir correspondant au montant deja verse.</p>

                    <div>
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#244e76;">
                            <input type="checkbox" name="present_on_site" value="1" {{ old('present_on_site') ? 'checked' : '' }} required>
                            Le client est present sur site.
                        </label>
                    </div>

                    <div>
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#244e76;">
                            <input type="checkbox" name="termination_signed" value="1" {{ old('termination_signed') ? 'checked' : '' }} required>
                            Le contrat de resiliation est signe.
                        </label>
                    </div>

                    <div>
                        <label>Montant qui sera credite en avoir</label>
                        <input type="text" readonly value="{{ number_format($totalPaid, 2, '.', ' ') }}">
                    </div>

                    <div>
                        <label for="cancel-note">Note</label>
                        <textarea id="cancel-note" name="note" rows="3">{{ old('note') }}</textarea>
                    </div>

                    @if ($activeLinkedAdditionalServiceRows->isNotEmpty())
                        <div style="display:grid;gap:6px;border:1px solid #dbe7f4;border-radius:10px;padding:10px;background:#f8fbff;">
                            <strong style="font-size:13px;color:#1f4970;">Annuler aussi les reservations de services supplementaires ?</strong>
                            <small style="color:#4f6b86;">Selection service par service.</small>
                            @php
                                $oldCancelLinkedIds = collect(old('cancel_linked_reservation_ids', []))->map(fn ($id) => (string) $id);
                            @endphp
                            @foreach ($activeLinkedAdditionalServiceRows as $serviceRow)
                                @php
                                    $linked = $serviceRow->linkedReservation;
                                    $serviceLabel = $additionalServiceModules[$serviceRow->module_slug] ?? ucfirst((string) $serviceRow->module_slug);
                                @endphp
                                <label style="display:flex;align-items:flex-start;gap:8px;font-size:13px;color:#244e76;margin:0;">
                                    <input type="checkbox" name="cancel_linked_reservation_ids[]" value="{{ $linked->id }}" {{ $oldCancelLinkedIds->contains((string) $linked->id) ? 'checked' : '' }}>
                                    <span>
                                        {{ $serviceLabel }} - {{ $serviceRow->label }} (Reservation #{{ $linked->id }})
                                        <small style="display:block;color:#607a95;">Date: @frDate($linked->start_date) @if($linked->start_date !== $linked->end_date) -> @frDate($linked->end_date) @endif | Heure: {{ $linked->start_time ?? '--:--' }} - {{ $linked->end_time ?? '--:--' }}</small>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    <div class="reservation-actions-row">
                        <button type="submit" class="btn" style="border-color:#efc1bf;color:#a9362f;background:#fff3f2;">Confirmer annulation et creer avoir</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($canCreateReservation)
            <div class="modal-overlay" id="clone-reservation-modal">
                <div class="modal-card">
                    <div class="modal-head">
                        <h3 class="modal-title">Cloner la reservation</h3>
                        <button type="button" class="btn" data-close-modal>Fermer</button>
                    </div>

                    <form method="POST" action="{{ route('reservations.clone', $reservation) }}" id="clone-reservation-form" style="display:grid; gap:10px;">
                        @csrf

                        <p class="payment-form-help">Tu peux ajuster les informations avant confirmation. Le clonage copie la reservation et les services supplementaires, mais ne copie aucun paiement.</p>

                        <div class="slot-inline-grid">
                            <div>
                                <label for="clone-title">Titre</label>
                                <input id="clone-title" name="clone_title" type="text" required value="{{ old('clone_title', 'Copie - ' . ($reservation->title ?: ('Reservation #' . $reservation->id))) }}">
                            </div>
                            <div>
                                <label for="clone-event-type">Type evenement</label>
                                <select id="clone-event-type" name="clone_event_type" required>
                                    @foreach ($eventTypes as $eventType)
                                        <option value="{{ $eventType }}" {{ old('clone_event_type', $reservation->event_type) === $eventType ? 'selected' : '' }}>{{ $eventType }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="clone-guest-count">Nombre invites</label>
                                <input id="clone-guest-count" name="clone_guest_count" type="number" min="1" value="{{ old('clone_guest_count', $reservation->guest_count) }}">
                            </div>
                            <div>
                                <label for="clone-total-amount">Montant total</label>
                                <input id="clone-total-amount" name="clone_total_amount" type="number" step="0.01" min="0" value="{{ old('clone_total_amount', number_format((float) $reservation->total_amount, 2, '.', '')) }}">
                            </div>

                            <div>
                                <label for="clone-start-date">Date event</label>
                                <input id="clone-start-date" name="clone_start_date" type="date" required value="{{ old('clone_start_date', $reservation->start_date) }}">
                            </div>
                            <div>
                                <label for="clone-end-date">Date fin</label>
                                <input id="clone-end-date" name="clone_end_date" type="date" required value="{{ old('clone_end_date', $reservation->end_date) }}">
                            </div>

                            <div>
                                <label for="clone-start-time">Heure debut</label>
                                <input id="clone-start-time" name="clone_start_time" type="time" required value="{{ old('clone_start_time', $reservation->start_time ? \Carbon\Carbon::parse($reservation->start_time)->format('H:i') : '') }}">
                            </div>
                            <div>
                                <label for="clone-end-time">Heure fin</label>
                                <input id="clone-end-time" name="clone_end_time" type="time" required value="{{ old('clone_end_time', $reservation->end_time ? \Carbon\Carbon::parse($reservation->end_time)->format('H:i') : '') }}">
                            </div>

                            <div style="grid-column:1/-1;">
                                <label for="clone-salle-id">Salle</label>
                                <select id="clone-salle-id" name="clone_salle_id" required>
                                    @foreach ($salles as $salle)
                                        <option value="{{ $salle->id }}" {{ (string) old('clone_salle_id', $reservation->salle_id) === (string) $salle->id ? 'selected' : '' }}>
                                            {{ $salle->name }} (Cap: {{ $salle->capacity ?? '-' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="clone-note-admin">Note admin</label>
                            <textarea id="clone-note-admin" name="clone_note_admin" rows="3">{{ old('clone_note_admin', $reservation->note_admin) }}</textarea>
                        </div>

                        <div style="display:grid;gap:8px;">
                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#244e76;">
                                <input type="checkbox" name="copy_additional_services" value="1" {{ old('copy_additional_services', '1') === '1' ? 'checked' : '' }}>
                                Copier les services supplementaires
                            </label>
                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#244e76;">
                                <input type="checkbox" name="copy_salle_options" value="1" {{ old('copy_salle_options', '1') === '1' ? 'checked' : '' }}>
                                Copier les options salle (copie uniquement si la salle reste la meme)
                            </label>
                        </div>

                        <div class="reservation-actions-row">
                            <button type="submit" class="btn btn-primary">Confirmer le clonage</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="modal-overlay" id="reservation-slot-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Modifier date/heure/salle</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>

                <form method="POST" action="{{ route('reservations.slot.update', $reservation) }}" id="reservation-slot-form" style="display:grid; gap:10px;">
                    @csrf
                    @method('PATCH')

                    <div class="slot-inline-grid">
                        <div>
                            <label for="slot-start-date">Date event</label>
                            <input id="slot-start-date" name="start_date" type="date" required value="{{ old('start_date', $reservation->start_date) }}">
                        </div>
                        <input id="slot-end-date" name="end_date" type="hidden" value="{{ old('end_date', $reservation->end_date) }}">

                        <div>
                            <label for="slot-start-time">Heure debut</label>
                            <input id="slot-start-time" name="start_time" type="time" required value="{{ old('start_time', $reservation->start_time ? \Carbon\Carbon::parse($reservation->start_time)->format('H:i') : '') }}">
                        </div>
                        <div>
                            <label for="slot-end-time">Heure fin</label>
                            <input id="slot-end-time" name="end_time" type="time" required value="{{ old('end_time', $reservation->end_time ? \Carbon\Carbon::parse($reservation->end_time)->format('H:i') : '') }}">
                        </div>
                    </div>

                    <p class="payment-form-help" id="slot-availability-help">La disponibilite est verifiee automatiquement des que date/heure changent.</p>

                    <div>
                        <label>Salles disponibles</label>
                        <input type="hidden" id="slot-salle-id" name="salle_id" value="{{ old('salle_id', $reservation->salle_id) }}" required>
                        <div class="salle-card-grid" id="slot-salle-cards">
                            <button type="button" class="salle-card is-selected" data-salle-id="{{ $reservation->salle_id }}">
                                <span class="salle-card-title">
                                    <span class="salle-color-dot js-salle-dot" data-color="{{ $currentSalleColor }}"></span>
                                    {{ $reservation->salle?->name ?? 'Salle actuelle' }}
                                </span>
                                <span class="salle-card-meta">Salle actuelle</span>
                            </button>
                        </div>
                    </div>

                    @if ($activeLinkedAdditionalServiceRows->isNotEmpty())
                        <div style="display:grid;gap:6px;border:1px solid #dbe7f4;border-radius:10px;padding:10px;background:#f8fbff;">
                            <strong style="font-size:13px;color:#1f4970;">Modifier aussi la date des reservations de services supplementaires ?</strong>
                            <small style="color:#4f6b86;">Selection service par service. Les heures restent inchangees.</small>
                            @php
                                $oldSyncLinkedIds = collect(old('sync_linked_date_ids', []))->map(fn ($id) => (string) $id);
                            @endphp
                            @foreach ($activeLinkedAdditionalServiceRows as $serviceRow)
                                @php
                                    $linked = $serviceRow->linkedReservation;
                                    $serviceLabel = $additionalServiceModules[$serviceRow->module_slug] ?? ucfirst((string) $serviceRow->module_slug);
                                @endphp
                                <label style="display:flex;align-items:flex-start;gap:8px;font-size:13px;color:#244e76;margin:0;">
                                    <input type="checkbox" name="sync_linked_date_ids[]" value="{{ $linked->id }}" {{ $oldSyncLinkedIds->contains((string) $linked->id) ? 'checked' : '' }}>
                                    <span>
                                        {{ $serviceLabel }} - {{ $serviceRow->label }} (Reservation #{{ $linked->id }})
                                        <small style="display:block;color:#607a95;">Date actuelle: @frDate($linked->start_date) @if($linked->start_date !== $linked->end_date) -> @frDate($linked->end_date) @endif | Heure conservee: {{ $linked->start_time ?? '--:--' }} - {{ $linked->end_time ?? '--:--' }}</small>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    <div class="reservation-actions-row">
                        <button type="submit" class="btn btn-primary">Enregistrer date/heure/salle</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay" id="reservation-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Modifier reservation</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>

                <form method="POST" action="{{ route('reservations.details.update', $reservation) }}" style="display:grid; gap:10px;">
                    @csrf
                    @method('PATCH')

                    <p class="payment-form-help">Toute modification de salle/date/heure est validee contre la disponibilite reelle. En cas de conflit, la mise a jour sera refusee.</p>

                    <div class="client-form-grid">
                        <div>
                            <label for="reservation-title">Titre</label>
                            <input id="reservation-title" name="title" type="text" maxlength="255" required value="{{ old('title', $reservation->title) }}">
                        </div>
                        <div>
                            <label for="reservation-event-type">Type de l event</label>
                            <select id="reservation-event-type" name="event_type" required>
                                <option value=""></option>
                                @foreach (($eventTypes ?? []) as $eventType)
                                    <option value="{{ $eventType }}" {{ old('event_type', $reservation->event_type) === $eventType ? 'selected' : '' }}>{{ $eventType }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="reservation-guest-count">Nombre des invites</label>
                            <input id="reservation-guest-count" name="guest_count" type="number" min="1" step="1" value="{{ old('guest_count', $reservation->guest_count) }}">
                        </div>
                        <div>
                            <label for="reservation-total-amount">Montant total</label>
                            <input id="reservation-total-amount" name="total_amount" type="number" min="0" step="0.01" value="{{ old('total_amount', $reservation->total_amount) }}">
                        </div>
                        <div class="full">
                            <label for="reservation-note-admin">Note administrative</label>
                            <textarea id="reservation-note-admin" name="note_admin" rows="3">{{ old('note_admin', $reservation->note_admin) }}</textarea>
                        </div>
                    </div>
                    <div class="reservation-actions-row">
                        <button type="submit" class="btn btn-primary">Enregistrer reservation</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay" id="client-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Mettre a jour les donnees client</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>

                <form method="POST" action="{{ route('reservations.client.update', $reservation) }}" style="display:grid; gap:10px;">
                    @csrf
                    @method('PATCH')
                    <div class="client-form-grid">
                        <div>
                            <label for="client-type">Type client</label>
                            <select id="client-type" name="client_type" required>
                                <option value="personne-physique" {{ old('client_type', $reservation->client?->client_type) === 'personne-physique' ? 'selected' : '' }}>Personne physique</option>
                                <option value="societe" {{ old('client_type', $reservation->client?->client_type) === 'societe' ? 'selected' : '' }}>Societe</option>
                            </select>
                        </div>
                        <div>
                            <label for="client-cin">CIN (non modifiable)</label>
                            <input id="client-cin" type="text" value="{{ $reservation->client?->cin ?? '-' }}" readonly>
                        </div>

                        <div>
                            <label for="client-first-name">Prenom</label>
                            <input id="client-first-name" name="first_name" type="text" value="{{ old('first_name', $reservation->client?->first_name) }}" required>
                        </div>
                        <div>
                            <label for="client-name">Nom</label>
                            <input id="client-name" name="name" type="text" value="{{ old('name', $reservation->client?->name) }}" required>
                        </div>

                        <div>
                            <label for="client-fiscal">Matricule fiscale</label>
                            <input id="client-fiscal" name="fiscal_number" type="text" value="{{ old('fiscal_number', $reservation->client?->fiscal_number) }}">
                        </div>
                        <div>
                            <label for="client-company">Raison sociale</label>
                            <input id="client-company" name="company_name" type="text" value="{{ old('company_name', $reservation->client?->company_name) }}">
                        </div>

                        <div>
                            <label for="client-date-cin">Date delivrance CIN</label>
                            <input id="client-date-cin" name="date_cin" type="date" value="{{ old('date_cin', $reservation->client?->date_cin) }}">
                        </div>
                        <div>
                            <label for="client-email">Email</label>
                            <input id="client-email" name="email" type="email" value="{{ old('email', $reservation->client?->email) }}">
                        </div>

                        <div>
                            <label for="client-phone">Mobile 1</label>
                            <input id="client-phone" name="phone" type="text" value="{{ old('phone', $reservation->client?->phone) }}" required>
                        </div>
                        <div>
                            <label for="client-phone-label-1">Label mobile 1</label>
                            <input id="client-phone-label-1" name="phone_label_1" type="text" value="{{ old('phone_label_1', $reservation->client?->phone_label_1) }}">
                        </div>

                        <div>
                            <label for="client-phone-2">Mobile 2</label>
                            <input id="client-phone-2" name="phone_2" type="text" value="{{ old('phone_2', $reservation->client?->phone_2) }}">
                        </div>
                        <div>
                            <label for="client-phone-label-2">Label mobile 2</label>
                            <input id="client-phone-label-2" name="phone_label_2" type="text" value="{{ old('phone_label_2', $reservation->client?->phone_label_2) }}">
                        </div>

                        <div>
                            <label for="client-address-number">N adresse</label>
                            <input id="client-address-number" name="address_number" type="text" value="{{ old('address_number', $reservation->client?->address_number) }}">
                        </div>
                        <div>
                            <label for="client-address-street">Rue</label>
                            <input id="client-address-street" name="address_street" type="text" value="{{ old('address_street', $reservation->client?->address_street) }}">
                        </div>

                        <div>
                            <label for="client-city">Ville</label>
                            <input id="client-city" name="city" type="text" value="{{ old('city', $reservation->client?->city) }}">
                        </div>
                        <div>
                            <label for="client-governorate">Gouvernorat</label>
                            <select id="client-governorate" name="governorate" required>
                                @foreach ($governorates as $governorate)
                                    <option value="{{ $governorate }}" {{ old('governorate', $reservation->client?->governorate) === $governorate ? 'selected' : '' }}>{{ $governorate }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="client-source">D ou avez-vous connu Queen PARK Tunisie ?</label>
                            <select id="client-source" name="source" required>
                                <option value="">Selectionner</option>
                                @foreach ($sources as $sourceValue => $sourceLabel)
                                    <option value="{{ $sourceValue }}" {{ old('source', $reservation->client?->source) === $sourceValue ? 'selected' : '' }}>{{ $sourceLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="full">
                            <label for="client-note">Note</label>
                            <textarea id="client-note" name="note" rows="3">{{ old('note', $reservation->client?->note) }}</textarea>
                        </div>
                    </div>
                    <div class="reservation-actions-row">
                        <button type="submit" class="btn btn-primary">Enregistrer client</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($canUpdateReservation && $isSalleReservation)
        <div class="modal-overlay" id="reservation-salle-option-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Ajouter une option salle</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>

                <form method="POST" action="{{ route('reservations.salle-options.store', $reservation) }}" class="payment-form">
                    @csrf

                    <div class="payment-form-row">
                        <div>
                            <label for="reservation-salle-option-id">Option</label>
                            <select id="reservation-salle-option-id" name="salle_option_id" required>
                                <option value="">Selectionner</option>
                                @foreach (($availableSalleOptions ?? collect()) as $option)
                                    <option value="{{ $option->id }}" data-default-amount="{{ (float) $option->price }}">{{ $option->name }} ({{ number_format((float) $option->price, 2, '.', ' ') }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="reservation-salle-option-amount">Montant</label>
                            <input id="reservation-salle-option-amount" name="amount" type="number" step="0.01" min="0" placeholder="Laisser vide = prix option">
                        </div>
                    </div>

                    <div>
                        <label for="reservation-salle-option-note">Note</label>
                        <input id="reservation-salle-option-note" name="note" type="text" value="{{ old('note') }}" placeholder="Optionnel">
                    </div>

                    <p class="payment-form-help">Prix autorise a 0 pour une option gratuite.</p>

                    <div class="reservation-actions-row">
                        <button type="submit" class="btn btn-primary">Ajouter option</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal-overlay" id="additional-service-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Ajouter un service supplementaire</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>

                <form method="POST" action="{{ route('reservations.additional-services.store', $reservation) }}" class="payment-form" id="additional-service-form">
                    @csrf
                    <div class="payment-form-row">
                        <div>
                            <label for="additional-service-module">Categorie</label>
                            <select id="additional-service-module" name="module_slug" required>
                                @foreach ($additionalServiceModules as $slug => $label)
                                    <option value="{{ $slug }}" {{ old('module_slug') === $slug ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="additional-service-ref">Service</label>
                            <select id="additional-service-ref" name="service_ref" required></select>
                        </div>
                    </div>

                    <div class="payment-form-row">
                        <div>
                            <label for="additional-service-amount">Montant</label>
                            <input id="additional-service-amount" name="service_amount" type="number" step="0.01" min="0" value="{{ old('service_amount') }}">
                        </div>
                        <div>
                            <label for="additional-service-note">Note</label>
                            <input id="additional-service-note" name="note" type="text" value="{{ old('note') }}" placeholder="Optionnel">
                        </div>
                    </div>

                    <p class="payment-form-help" id="additional-service-help">Selectionne une categorie puis un service.</p>

                    <div class="reservation-actions-row">
                        <button type="submit" class="btn btn-primary">Ajouter service</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($canCreatePayment)
        <div class="modal-overlay" id="payment-modal">
            <div class="modal-card">
                <div class="modal-head">
                    <h3 class="modal-title">Ajouter paiement relatif</h3>
                    <button type="button" class="btn" data-close-modal>Fermer</button>
                </div>

                <form method="POST" action="{{ route('reservations.payments.store', $reservation) }}" class="payment-form" id="reservation-payment-form">
                    @csrf
                    <div class="payment-form-row">
                        <div>
                            <label for="payment-phase">Type paiement</label>
                            <select id="payment-phase" name="phase" required>
                                <option value="avance" {{ old('phase', $nextPhase) === 'avance' ? 'selected' : '' }}>Avance</option>
                                <option value="partie-1" {{ old('phase', $nextPhase) === 'partie-1' ? 'selected' : '' }}>Partie 1</option>
                                <option value="partie-2" {{ old('phase', $nextPhase) === 'partie-2' ? 'selected' : '' }}>Partie 2</option>
                                <option value="partie-3" {{ old('phase', $nextPhase) === 'partie-3' ? 'selected' : '' }}>Partie 3</option>
                                <option value="reste" {{ old('phase', $nextPhase) === 'reste' ? 'selected' : '' }}>Reste</option>
                            </select>
                        </div>
                        <div>
                            <label for="payment-amount">Montant</label>
                            <input id="payment-amount" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required>
                        </div>
                    </div>

                    <div class="payment-form-row">
                        <div>
                            <label for="payment-method">Methode</label>
                            <select id="payment-method" name="method" required>
                                <option value="cash" {{ old('method') === 'cash' ? 'selected' : '' }}>Cash</option>
                                <option value="virement" {{ old('method') === 'virement' ? 'selected' : '' }}>Virement</option>
                                <option value="carte" {{ old('method') === 'carte' ? 'selected' : '' }}>Carte</option>
                                <option value="cheque" {{ old('method') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="avoir" {{ old('method') === 'avoir' ? 'selected' : '' }}>Avoir client</option>
                            </select>
                        </div>
                        <div>
                            <label for="payment-paid-at-display">Date paiement (auto)</label>
                            <input id="payment-paid-at-display" type="text" value="{{ now()->format('d/m/Y H:i:s') }}" readonly>
                        </div>
                    </div>

                    <div>
                        <label for="payment-note">Note</label>
                        <textarea id="payment-note" name="note" rows="2" placeholder="Note optionnelle">{{ old('note') }}</textarea>
                    </div>

                    <p class="payment-form-help" id="payment-form-help">Controle: le premier paiement doit etre "Avance". Reste actuel: {{ number_format($remainingAmount, 2, '.', ' ') }}. Solde client disponible ({{ $creditServiceLabel }}): {{ number_format($clientCreditBalance, 2, '.', ' ') }}.</p>

                    <div class="reservation-actions-row">
                        <button type="submit" class="btn btn-primary" {{ $remainingAmount <= 0 ? 'disabled' : '' }}>Ajouter paiement</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script type="application/json" id="additional-service-options-data">{!! json_encode($serviceOptionsByModule ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    <script>
        (function () {
            document.querySelectorAll('.js-salle-dot').forEach((dot) => {
                dot.style.backgroundColor = dot.dataset.color || '#8ea9c4';
            });

            const slotStartDate = document.getElementById('slot-start-date');
            const slotStartTime = document.getElementById('slot-start-time');
            const slotEndTime = document.getElementById('slot-end-time');
            const slotSalleInput = document.getElementById('slot-salle-id');
            const slotSalleCards = document.getElementById('slot-salle-cards');
            const slotHelp = document.getElementById('slot-availability-help');
            const availableSallesBaseUrl = "{{ route('reservations.available-salles', $reservation) }}";

            if (!slotStartDate || !slotStartTime || !slotEndTime || !slotSalleInput || !slotSalleCards) {
                return;
            }

            const clearSallesAvailability = (message) => {
                slotSalleCards.innerHTML = '';
                slotSalleInput.value = '';
                if (slotHelp && message) {
                    slotHelp.textContent = message;
                }
            };

            const setSelectedSalle = (salleId) => {
                slotSalleInput.value = salleId ? String(salleId) : '';
                slotSalleCards.querySelectorAll('.salle-card').forEach((card) => {
                    card.classList.toggle('is-selected', String(card.dataset.salleId) === String(salleId));
                });
            };

            slotSalleCards.addEventListener('click', (event) => {
                const card = event.target.closest('.salle-card');
                if (!card || !card.dataset.salleId) {
                    return;
                }
                setSelectedSalle(card.dataset.salleId);
            });

            const slotEndDate = document.getElementById('slot-end-date');
            if (slotEndDate) {
                const syncEndDate = () => {
                    slotEndDate.value = slotStartDate.value;
                };
                slotStartDate.addEventListener('change', syncEndDate);
                slotStartDate.addEventListener('input', syncEndDate);
                syncEndDate();
            }

            const runAvailabilityCheck = async () => {
                const startDate = slotStartDate.value;
                const startTime = slotStartTime.value;
                const endTime = slotEndTime.value;

                if (!startDate || !startTime || !endTime) {
                    clearSallesAvailability('Renseigne date, heure debut et heure fin.');
                    return;
                }

                clearSallesAvailability('Verification des salles disponibles...');
                if (slotHelp) slotHelp.textContent = 'Verification des salles disponibles...';

                try {
                    const params = new URLSearchParams({
                        event_date: startDate,
                        start_time: startTime,
                        end_time: endTime,
                        exclude_reservation_id: "{{ $reservation->id }}",
                    });

                    const response = await fetch(`${availableSallesBaseUrl}?${params.toString()}`, {
                        headers: { Accept: 'application/json' },
                    });

                    const payload = await response.json();

                    if (!response.ok) {
                        const firstError = Object.values(payload?.errors || {})?.[0]?.[0];
                        if (slotHelp) {
                            slotHelp.textContent = firstError || payload?.message || 'Parametres invalides pour la verification.';
                        }
                        return;
                    }

                    const salles = Array.isArray(payload?.salles) ? payload.salles : [];

                    slotSalleCards.innerHTML = '';
                    if (salles.length === 0) {
                        slotSalleInput.value = '';
                        const empty = document.createElement('div');
                        empty.className = 'salle-card';
                        empty.style.cursor = 'default';
                        empty.textContent = 'Aucune salle disponible';
                        slotSalleCards.appendChild(empty);
                        if (slotHelp) slotHelp.textContent = 'Aucune salle disponible pour ce creneau.';
                        return;
                    }

                    let selectedSalleId = '';
                    salles.forEach((salle) => {
                        const card = document.createElement('button');
                        card.type = 'button';
                        card.className = 'salle-card';
                        card.dataset.salleId = String(salle.id);
                        card.innerHTML = `
                            <span class="salle-card-title">
                                <span class="salle-color-dot" style="background-color: ${salle.color_code || '#8ea9c4'}"></span>
                                ${salle.name}
                            </span>
                            <span class="salle-card-meta">Capacite: ${salle.capacity ?? '-'} | Prix/j: ${salle.price_per_day ?? '-'}</span>
                        `;
                        slotSalleCards.appendChild(card);

                        if (selectedSalleId === '' && Number(salle.id) === Number("{{ $reservation->salle_id }}")) {
                            selectedSalleId = String(salle.id);
                        }
                    });

                    if (selectedSalleId === '' && salles[0]) {
                        selectedSalleId = String(salles[0].id);
                    }
                    setSelectedSalle(selectedSalleId);

                    if (slotHelp) slotHelp.textContent = `${salles.length} salle(s) disponible(s). Selectionne puis enregistre.`;
                } catch (error) {
                    if (slotHelp) slotHelp.textContent = 'Impossible de verifier la disponibilite pour le moment.';
                }
            };

            let availabilityDebounceTimer = null;
            const scheduleAvailabilityCheck = () => {
                clearSallesAvailability('Date/heure modifiee. Mise a jour de la disponibilite...');
                if (availabilityDebounceTimer) {
                    clearTimeout(availabilityDebounceTimer);
                }
                availabilityDebounceTimer = setTimeout(() => {
                    runAvailabilityCheck();
                }, 250);
            };

            slotStartDate.addEventListener('change', scheduleAvailabilityCheck);
            slotStartDate.addEventListener('input', scheduleAvailabilityCheck);
            slotStartTime.addEventListener('change', scheduleAvailabilityCheck);
            slotStartTime.addEventListener('input', scheduleAvailabilityCheck);
            slotEndTime.addEventListener('change', scheduleAvailabilityCheck);
            slotEndTime.addEventListener('input', scheduleAvailabilityCheck);

            runAvailabilityCheck();
        })();
    </script>

    <script>
        (function () {
            const overlays = document.querySelectorAll('.modal-overlay');
            const openers = document.querySelectorAll('[data-open-modal]');
            const closers = document.querySelectorAll('[data-close-modal]');
            const actionsToggle = document.getElementById('reservation-actions-toggle');
            const actionsPanel = document.getElementById('reservation-actions-panel');
            const actionsMenu = document.getElementById('reservation-actions-menu');

            const openModal = (id) => {
                const target = document.getElementById(id);
                if (target) {
                    target.classList.add('show');
                }
            };

            const closeModal = (element) => {
                if (element) {
                    element.classList.remove('show');
                }
            };

            openers.forEach((button) => {
                button.addEventListener('click', () => {
                    const modalId = button.getAttribute('data-open-modal');
                    if (modalId) {
                        openModal(modalId);
                        if (actionsPanel) {
                            actionsPanel.classList.remove('show');
                        }
                    }
                });
            });

            if (actionsToggle && actionsPanel && actionsMenu) {
                actionsToggle.addEventListener('click', (event) => {
                    event.stopPropagation();
                    actionsPanel.classList.toggle('show');
                });

                document.addEventListener('click', (event) => {
                    if (!actionsMenu.contains(event.target)) {
                        actionsPanel.classList.remove('show');
                    }
                });
            }

            closers.forEach((button) => {
                button.addEventListener('click', () => {
                    closeModal(button.closest('.modal-overlay'));
                });
            });

            overlays.forEach((overlay) => {
                overlay.addEventListener('click', (event) => {
                    if (event.target === overlay) {
                        closeModal(overlay);
                    }
                });
            });

            const paymentPhase = document.getElementById('payment-phase');
            const paymentAmount = document.getElementById('payment-amount');
            const paymentHelp = document.getElementById('payment-form-help');
            const remainingValue = Number("{{ number_format($remainingAmount, 2, '.', '') }}");
            const paymentCountValue = Number("{{ $paymentCount }}");

            const syncPaymentControls = () => {
                if (!paymentPhase || !paymentAmount) {
                    return;
                }

                if (paymentCountValue === 0 && paymentPhase.value !== 'avance') {
                    paymentPhase.value = 'avance';
                }

                paymentAmount.max = remainingValue > 0 ? String(remainingValue) : '0';

                if (paymentPhase.value === 'reste' && remainingValue > 0) {
                    paymentAmount.value = remainingValue.toFixed(2);
                    paymentAmount.readOnly = true;
                } else {
                    paymentAmount.readOnly = false;
                }

                if (paymentHelp) {
                    paymentHelp.textContent = `Controle: premier paiement = Avance. Reste actuel: ${remainingValue.toFixed(2)}.`;
                }
            };

            if (paymentPhase) {
                paymentPhase.addEventListener('change', syncPaymentControls);
                syncPaymentControls();
            }

            const additionalServiceOptionsData = document.getElementById('additional-service-options-data');
            const reservationSalleOptionSelect = document.getElementById('reservation-salle-option-id');
            const reservationSalleOptionAmount = document.getElementById('reservation-salle-option-amount');
            let serviceOptionsByModule = {};
            if (additionalServiceOptionsData) {
                try {
                    serviceOptionsByModule = JSON.parse(additionalServiceOptionsData.textContent || '{}');
                } catch (error) {
                    serviceOptionsByModule = {};
                }
            }
            const additionalServiceModule = document.getElementById('additional-service-module');
            const additionalServiceRef = document.getElementById('additional-service-ref');
            const additionalServiceAmount = document.getElementById('additional-service-amount');
            const additionalServiceHelp = document.getElementById('additional-service-help');

            if (reservationSalleOptionSelect && reservationSalleOptionAmount) {
                reservationSalleOptionSelect.addEventListener('change', () => {
                    const selected = reservationSalleOptionSelect.options[reservationSalleOptionSelect.selectedIndex];
                    if (!selected) {
                        return;
                    }

                    const defaultAmount = selected.getAttribute('data-default-amount');
                    if (defaultAmount !== null && reservationSalleOptionAmount.value === '') {
                        reservationSalleOptionAmount.placeholder = `Prix option: ${Number(defaultAmount).toFixed(2)}`;
                    }
                });
            }

            const renderAdditionalServiceOptions = () => {
                if (!additionalServiceModule || !additionalServiceRef) {
                    return;
                }

                const slug = additionalServiceModule.value;
                const bucket = serviceOptionsByModule[slug] || { options: [] };
                const options = Array.isArray(bucket.options) ? bucket.options : [];

                additionalServiceRef.innerHTML = '';

                if (options.length === 0) {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Aucun service actif';
                    additionalServiceRef.appendChild(option);
                    additionalServiceRef.disabled = true;
                    if (additionalServiceAmount) {
                        additionalServiceAmount.value = '';
                    }
                    if (additionalServiceHelp) {
                        additionalServiceHelp.textContent = 'Aucun service actif dans cette categorie.';
                    }
                    return;
                }

                additionalServiceRef.disabled = false;
                options.forEach((entry) => {
                    const option = document.createElement('option');
                    option.value = String(entry.ref || '');
                    option.textContent = `${entry.kind}: ${entry.name}`;
                    option.dataset.amount = String(entry.amount ?? '0');
                    additionalServiceRef.appendChild(option);
                });

                const firstAmount = Number(additionalServiceRef.options[0]?.dataset?.amount || 0);
                if (additionalServiceAmount && Number.isFinite(firstAmount) && !additionalServiceAmount.value) {
                    additionalServiceAmount.value = firstAmount.toFixed(2);
                }
                if (additionalServiceHelp) {
                    additionalServiceHelp.textContent = 'Montant propose automatiquement selon le service choisi.';
                }
            };

            if (additionalServiceModule) {
                additionalServiceModule.addEventListener('change', renderAdditionalServiceOptions);
                renderAdditionalServiceOptions();
            }

            if (additionalServiceRef && additionalServiceAmount) {
                additionalServiceRef.addEventListener('change', () => {
                    const selected = additionalServiceRef.options[additionalServiceRef.selectedIndex];
                    const amount = Number(selected?.dataset?.amount || 0);
                    if (Number.isFinite(amount) && amount >= 0) {
                        additionalServiceAmount.value = amount.toFixed(2);
                    }
                });
            }

            const hasPaymentErrors = "{{ $errors->has('amount') || $errors->has('phase') || $errors->has('method') || $errors->has('note') ? '1' : '0' }}" === '1';
            const hasClientErrors = "{{ $errors->has('client_type') || $errors->has('first_name') || $errors->has('name') || $errors->has('phone') ? '1' : '0' }}" === '1';
            const hasAdditionalServiceErrors = "{{ $errors->has('module_slug') || $errors->has('service_ref') || $errors->has('service_amount') || $errors->has('service') ? '1' : '0' }}" === '1';
            const hasReservationErrors = "{{ $errors->has('title') || $errors->has('event_type') || $errors->has('guest_count') || $errors->has('total_amount') || $errors->has('note_admin') ? '1' : '0' }}" === '1';
            const hasSlotErrors = "{{ $errors->has('salle_id') || $errors->has('start_date') || $errors->has('end_date') || $errors->has('start_time') || $errors->has('end_time') || $errors->has('sync_linked_date_ids') ? '1' : '0' }}" === '1';
            const hasCancelErrors = "{{ $errors->has('present_on_site') || $errors->has('termination_signed') || $errors->has('cancel') || $errors->has('cancel_linked_reservation_ids') ? '1' : '0' }}" === '1';
            const hasCreditErrors = "{{ $errors->has('credit') || $errors->has('credit_amount') ? '1' : '0' }}" === '1';
            const hasCreditTransferErrors = "{{ $errors->has('credit_transfer') || $errors->has('credit_transfer_amount') || $errors->has('target_client_id') ? '1' : '0' }}" === '1';
            const hasCloneErrors = "{{ $errors->has('clone_salle_id') || $errors->has('clone_title') || $errors->has('clone_event_type') || $errors->has('clone_start_date') || $errors->has('clone_end_date') || $errors->has('clone_start_time') || $errors->has('clone_end_time') || $errors->has('clone_total_amount') || $errors->has('clone_note_admin') ? '1' : '0' }}" === '1';
            const hasStaffAffectationErrors = "{{ $errors->has('staff_affectation') || $errors->has('manager_staff_user_id') || $errors->has('serveur_staff_user_ids') || $errors->has('serveur_staff_user_ids.*') || $errors->has('annimateur_staff_user_ids') || $errors->has('annimateur_staff_user_ids.*') || $errors->has('femme_menage_staff_user_ids') || $errors->has('femme_menage_staff_user_ids.*') || $errors->has('agent_securite_staff_user_ids') || $errors->has('agent_securite_staff_user_ids.*') ? '1' : '0' }}" === '1';

            if (hasCancelErrors) {
                openModal('cancel-reservation-modal');
            } else if (hasCloneErrors) {
                openModal('clone-reservation-modal');
            } else if (hasSlotErrors) {
                openModal('reservation-slot-modal');
            } else if (hasReservationErrors) {
                openModal('reservation-modal');
            } else if (hasPaymentErrors) {
                openModal('payment-modal');
            } else if (hasAdditionalServiceErrors) {
                openModal('additional-service-modal');
            } else if (hasClientErrors) {
                openModal('client-modal');
            } else if (hasStaffAffectationErrors) {
                openModal('staff-affectation-modal');
            }

            const staffDepartmentFilter = document.getElementById('staff-department-filter');
            if (staffDepartmentFilter) {
                const applyStaffDepartmentFilter = () => {
                    const selectedDepartmentId = String(staffDepartmentFilter.value || '');
                    document.querySelectorAll('#staff-affectation-modal .staff-avatar-card').forEach((card) => {
                        const cardDepartmentId = String(card.getAttribute('data-department-id') || '');
                        const shouldShow = selectedDepartmentId === '' || cardDepartmentId === '' || cardDepartmentId === selectedDepartmentId;
                        card.style.display = shouldShow ? '' : 'none';
                    });
                };

                staffDepartmentFilter.addEventListener('change', applyStaffDepartmentFilter);
                applyStaffDepartmentFilter();
            }

            const summarySwitch = document.getElementById('reservation-summary-switch');
            if (summarySwitch) {
                const switchButtons = Array.from(summarySwitch.querySelectorAll('[data-switch-target]'));
                const switchPanels = Array.from(document.querySelectorAll('[data-switch-panel]'));

                const activatePanel = (target) => {
                    switchButtons.forEach((button) => {
                        button.classList.toggle('is-active', button.getAttribute('data-switch-target') === target);
                    });

                    switchPanels.forEach((panel) => {
                        panel.classList.toggle('is-active', panel.getAttribute('data-switch-panel') === target);
                    });
                };

                switchButtons.forEach((button) => {
                    button.addEventListener('click', () => activatePanel(button.getAttribute('data-switch-target')));
                });
            }
        })();
    </script>
    <script>
        document.querySelectorAll('.js-service-start-time').forEach(function (input) {
            input.addEventListener('change', function () {
                var status = input.parentElement.querySelector('.js-start-time-status');
                status.textContent = '...';
                fetch(input.dataset.url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': input.dataset.token,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ start_time: input.value }),
                })
                .then(function (r) {
                    status.textContent = r.ok ? '✓' : '✗';
                    setTimeout(function () { status.textContent = ''; }, 2000);
                })
                .catch(function () {
                    status.textContent = '✗';
                });
            });
        });
    </script>
@endsection
