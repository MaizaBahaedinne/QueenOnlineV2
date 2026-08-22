<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Dashboard' }} - QueenOnlineV2</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            background: linear-gradient(120deg, #f8f4e8 0%, #f0e6cf 100%);
            color: #1f2937;
        }
        .container {
            max-width: 980px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .hero {
            background: #fff;
            border: 1px solid #e5dfcf;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
        }
        h1 {
            margin: 0 0 10px;
            font-size: 32px;
            line-height: 1.2;
        }
        p {
            margin: 0;
            color: #4b5563;
        }
        .grid {
            margin-top: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }
        .card {
            display: block;
            text-decoration: none;
            padding: 16px;
            border-radius: 10px;
            border: 1px solid #e6d9bf;
            background: #fffdfa;
            color: #111827;
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }
        .note {
            margin-top: 18px;
            font-size: 14px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <section class="hero">
            <h1>QueenOnlineV2</h1>
            <p>Application accessible. Le serveur fonctionne correctement.</p>

            <div class="grid">
                <a class="card" href="{{ route('users.index') }}">Utilisateurs</a>
                <a class="card" href="{{ route('clients.index') }}">Clients</a>
                <a class="card" href="{{ route('salles.index') }}">Salles</a>
                <a class="card" href="{{ route('reservations.index') }}">Reservations</a>
                <a class="card" href="{{ route('payments.index') }}">Paiements</a>
            </div>

            <p class="note">Si une section reste vide, c'est normal: certaines vues CRUD sont encore des placeholders.</p>
        </section>
    </div>
</body>
</html>
