<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? 'Mot de passe oublie') . ' - QueenPark Admin' }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="admin-body">
    <div class="auth-shell" style="min-height:100vh; display:grid; place-items:center; padding:24px;">
        <section class="auth-card panel" style="width:min(430px,100%);">
            <h1 class="panel-title">Mot de passe oublie</h1>
            <p class="panel-sub">Un lien de reinitialisation sera envoye par email.</p>

            @if (session('status'))
                <p class="badge badge-success" style="margin-top:10px;">{{ session('status') }}</p>
            @endif

            <form method="POST" action="{{ route('password.email') }}" style="display:grid; gap:10px; margin-top:12px;">
                @csrf
                <input class="search" style="max-width:none;" type="email" name="email" value="{{ old('email') }}" placeholder="Email" required>
                @error('email')
                    <p class="badge badge-danger">{{ $message }}</p>
                @enderror
                <button class="btn btn-primary" type="submit">Envoyer le lien</button>
                <a href="{{ route('login') }}">Retour login</a>
            </form>
        </section>
    </div>
</body>
</html>
