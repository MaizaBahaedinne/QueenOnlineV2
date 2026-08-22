@extends('layouts.app')

@section('content')
    <section class="panel" style="max-width:620px;">
        <h1 class="panel-title">Changer mot de passe</h1>
        <p class="panel-sub">Mise a jour securisee du mot de passe utilisateur connecte.</p>

        @if (session('success'))
            <p class="badge badge-success" style="margin-top:10px;">{{ session('success') }}</p>
        @endif

        <form method="POST" action="{{ route('profile.password.update') }}" style="display:grid; gap:10px; margin-top:12px;">
            @csrf
            @method('PATCH')

            <input class="search" style="max-width:none;" type="password" name="current_password" placeholder="Mot de passe actuel" required>
            <input class="search" style="max-width:none;" type="password" name="password" placeholder="Nouveau mot de passe" required>
            <input class="search" style="max-width:none;" type="password" name="password_confirmation" placeholder="Confirmation" required>

            @if ($errors->any())
                <p class="badge badge-danger">{{ $errors->first() }}</p>
            @endif

            <button class="btn btn-primary" type="submit">Mettre a jour</button>
        </form>
    </section>
@endsection
