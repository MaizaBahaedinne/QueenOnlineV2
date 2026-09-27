@php
    $requestType = $requestType ?? 'contact';
    $formTitle = $requestType === 'quote' ? 'Demander un devis' : 'Envoyer un message';
@endphp

@if (session('success'))
    <div class="flash">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="error-box">
        <strong>Impossible d envoyer la demande.</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="form-card">
    <div class="form-copy" style="margin-bottom:18px;">
        <span class="eyebrow">{{ $requestType === 'quote' ? 'Devis' : 'Contact' }}</span>
        <h3 style="margin-top:14px;">{{ $formTitle }}</h3>
        <p style="margin-top:10px;">Decrivez votre besoin. Notre equipe utilisera ces informations pour vous repondre dans les meilleurs delais.</p>
    </div>

    <form method="POST" action="{{ route('site.inquiries.store') }}" class="form-grid">
        @csrf
        <input type="hidden" name="request_type" value="{{ $requestType }}">
        <input type="hidden" name="source_page" value="{{ request()->path() }}">

        <div class="field">
            <label for="{{ $requestType }}-full-name">Nom complet</label>
            <input id="{{ $requestType }}-full-name" type="text" name="full_name" value="{{ old('full_name') }}" required>
        </div>

        <div class="field">
            <label for="{{ $requestType }}-phone">Telephone</label>
            <input id="{{ $requestType }}-phone" type="text" name="phone" value="{{ old('phone') }}" required>
        </div>

        <div class="field">
            <label for="{{ $requestType }}-email">Email</label>
            <input id="{{ $requestType }}-email" type="email" name="email" value="{{ old('email') }}">
        </div>

        <div class="field">
            <label for="{{ $requestType }}-service-slug">Service concerne</label>
            <select id="{{ $requestType }}-service-slug" name="service_slug" {{ $requestType === 'quote' ? 'required' : '' }}>
                <option value="">Choisir un service</option>
                @foreach ($serviceOptions as $serviceOption)
                    <option value="{{ $serviceOption['slug'] }}" {{ old('service_slug', $selectedServiceSlug ?? '') === $serviceOption['slug'] ? 'selected' : '' }}>
                        {{ $serviceOption['name'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label for="{{ $requestType }}-event-date">Date souhaitee</label>
            <input id="{{ $requestType }}-event-date" type="date" name="event_date" value="{{ old('event_date') }}">
        </div>

        <div class="field">
            <label for="{{ $requestType }}-guest-count">Nombre d invites</label>
            <input id="{{ $requestType }}-guest-count" type="number" min="1" name="guest_count" value="{{ old('guest_count') }}">
        </div>

        <div class="field">
            <label for="{{ $requestType }}-budget">Budget estimatif</label>
            <input id="{{ $requestType }}-budget" type="number" step="0.01" min="0" name="budget" value="{{ old('budget') }}">
        </div>

        <div class="field full">
            <label for="{{ $requestType }}-message">Message</label>
            <textarea id="{{ $requestType }}-message" name="message" required>{{ old('message') }}</textarea>
        </div>

        <div class="field full">
            <button type="submit" class="btn btn-primary">{{ $requestType === 'quote' ? 'Envoyer ma demande de devis' : 'Envoyer mon message' }}</button>
        </div>
    </form>
</div>