<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? 'Reinitialiser mot de passe') . ' - QueenPark Admin' }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="admin-body">
    <div class="auth-shell" style="min-height:100vh; display:grid; place-items:center; padding:24px;">
        <section class="auth-card panel" style="width:min(430px,100%);">
            <h1 class="panel-title">Reinitialisation</h1>
            <p class="panel-sub">Definis un nouveau mot de passe.</p>

            <form method="POST" action="{{ route('password.update') }}" style="display:grid; gap:10px; margin-top:12px;">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input class="search" style="max-width:none;" type="email" name="email" value="{{ old('email', $email) }}" placeholder="Email" required>
                <input class="search" style="max-width:none;" type="password" name="password" placeholder="Nouveau mot de passe" required>
                <input class="search" style="max-width:none;" type="password" name="password_confirmation" placeholder="Confirmation" required>

                @if ($errors->any())
                    <p class="badge badge-danger">{{ $errors->first() }}</p>
                @endif

                <button class="btn btn-primary" type="submit">Reinitialiser</button>
            </form>
        </section>
    </div>
</body>
</html>
