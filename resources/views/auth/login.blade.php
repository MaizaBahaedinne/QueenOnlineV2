<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? 'Connexion') . ' - QueenPark Admin' }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        .auth-shell {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 24px;
        }

        .auth-card {
            width: min(430px, 100%);
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            box-shadow: var(--shadow);
            padding: 22px;
        }

        .auth-title {
            margin: 0 0 6px;
            font-size: 28px;
            color: var(--ink);
        }

        .auth-sub {
            margin: 0 0 16px;
            color: var(--muted);
            font-size: 14px;
        }

        .auth-grid {
            display: grid;
            gap: 10px;
        }

        .auth-input {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 10px 12px;
            font-size: 14px;
            color: var(--ink);
            background: #fff;
        }

        .auth-input:focus-visible {
            outline: 2px solid var(--primary-light);
            outline-offset: 1px;
            border-color: var(--primary);
        }

        .auth-foot {
            margin-top: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }

        .auth-error {
            margin: 0;
            color: var(--danger);
            font-size: 13px;
        }

        .auth-hint {
            margin-top: 10px;
            color: var(--muted);
            font-size: 12px;
        }
    </style>
</head>
<body class="admin-body">
    <div class="auth-shell">
        <section class="auth-card">
            <h1 class="auth-title">Connexion</h1>
            <p class="auth-sub">Acces a la plateforme QueenPark.</p>

            <form method="POST" action="{{ route('login.perform') }}" class="auth-grid">
                @csrf

                <input
                    class="auth-input"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="Email"
                    required
                    autofocus
                >

                <input
                    class="auth-input"
                    type="password"
                    name="password"
                    placeholder="Mot de passe"
                    required
                >

                <label style="font-size:13px; color:var(--muted); display:flex; gap:8px; align-items:center;">
                    <input type="checkbox" name="remember" value="1">
                    Se souvenir de moi
                </label>

                @if ($errors->any())
                    <p class="auth-error">{{ $errors->first() }}</p>
                @endif

                <div class="auth-foot">
                    <button type="submit" class="btn btn-primary">Se connecter</button>
                    <a href="{{ route('password.request') }}">Mot de passe oublie ?</a>
                </div>
            </form>

            <p class="auth-hint">Compte admin de test: admin@queenonline.test / password123</p>
        </section>
    </div>
</body>
</html>
