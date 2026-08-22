@extends('layouts.app')

@section('content')
    <section class="panel" style="max-width:760px;">
        <h1 class="panel-title">{{ $moduleMeta['name'] }}</h1>
        <p class="panel-sub">Le module est pret, mais ses tables ne sont pas encore migrees sur le serveur.</p>

        <div style="margin-top:12px; display:grid; gap:8px;">
            <p class="badge badge-warning">Action requise: migration de la base</p>
            <p class="muted">Execute sur le VPS:</p>
            <pre style="white-space:pre-wrap; margin:0; background:#fff; border:1px solid var(--line); border-radius:10px; padding:12px;">/usr/local/lsws/lsphp83/bin/php artisan migrate --force
/usr/local/lsws/lsphp83/bin/php artisan db:seed --force
/usr/local/lsws/lsphp83/bin/php artisan optimize:clear
/usr/local/lsws/lsphp83/bin/php artisan view:cache</pre>
        </div>
    </section>
@endsection
