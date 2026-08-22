<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clients</title>
</head>
<body style="font-family: Arial, sans-serif; margin: 24px;">
    <h1>Clients</h1>
    <p><a href="{{ route('dashboard') }}">Retour dashboard</a></p>
    <p>Nombre: {{ $clients->count() }}</p>
</body>
</html>
