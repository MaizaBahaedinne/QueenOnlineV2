<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('legacy:import-staff-avatars {--overwrite : Ecrase les photos existantes}', function () {
    $overwrite = (bool) $this->option('overwrite');

    if (! DB::getSchemaBuilder()->hasTable('staff')) {
        $this->error('Table staff introuvable. Lance d abord les migrations Laravel.');
        return;
    }

    $staffRows = DB::table('staff')
        ->select('id', 'user_id', 'photo_path')
        ->when(! $overwrite, fn ($query) => $query->where(function ($sub) {
            $sub->whereNull('photo_path')->orWhere('photo_path', '');
        }))
        ->whereNotNull('user_id')
        ->get();

    if ($staffRows->isEmpty()) {
        $this->info('Aucune fiche staff a traiter.');
        return;
    }

    $userIdToOldLegacyId = DB::table('migration_map_staff_users')
        ->pluck('old_user_id', 'new_user_id')
        ->map(fn ($oldId) => (int) $oldId)
        ->all();

    if (empty($userIdToOldLegacyId)) {
        $this->error('Table migration_map_staff_users vide ou absente. Lance le script SQL d import staff d abord.');
        return;
    }

    $legacyIds = collect($staffRows)
        ->pluck('user_id')
        ->filter(fn ($userId) => isset($userIdToOldLegacyId[(int) $userId]))
        ->map(fn ($userId) => $userIdToOldLegacyId[(int) $userId])
        ->unique()
        ->values();

    if ($legacyIds->isEmpty()) {
        $this->error('Aucune correspondance legacy trouvee pour les users staff.');
        return;
    }

    $legacyRows = DB::connection('legacy')
        ->table('tbl_users')
        ->select('userId', 'avatar')
        ->whereIn('userId', $legacyIds)
        ->get()
        ->keyBy('userId');

    $saved = 0;
    $skipped = 0;
    $errors = 0;

    foreach ($staffRows as $staffRow) {
        $newUserId = (int) $staffRow->user_id;
        $oldUserId = $userIdToOldLegacyId[$newUserId] ?? null;

        if (! $oldUserId || ! isset($legacyRows[$oldUserId])) {
            $skipped++;
            continue;
        }

        $avatarRaw = $legacyRows[$oldUserId]->avatar;
        if ($avatarRaw === null || $avatarRaw === '') {
            $skipped++;
            continue;
        }

        $binary = is_resource($avatarRaw) ? stream_get_contents($avatarRaw) : (string) $avatarRaw;
        if (! is_string($binary) || $binary === '') {
            $skipped++;
            continue;
        }

        $mime = null;
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->buffer($binary) ?: null;
        }

        $extension = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => null,
        };

        if ($extension === null) {
            // Fallback sur signatures courantes si le mime n est pas reconnu.
            if (str_starts_with($binary, "\xFF\xD8\xFF")) {
                $extension = 'jpg';
            } elseif (str_starts_with($binary, "\x89PNG\r\n\x1A\n")) {
                $extension = 'png';
            } elseif (str_starts_with($binary, 'GIF8')) {
                $extension = 'gif';
            } elseif (str_starts_with($binary, 'RIFF') && str_contains(substr($binary, 0, 16), 'WEBP')) {
                $extension = 'webp';
            }
        }

        if ($extension === null) {
            $errors++;
            $this->warn("Avatar non supporte pour old_user_id={$oldUserId} (staff_id={$staffRow->id}).");
            continue;
        }

        $path = 'legacy/avatars/staff-' . $staffRow->id . '-' . $oldUserId . '.' . $extension;

        try {
            Storage::disk('public')->put($path, $binary);
            DB::table('staff')->where('id', $staffRow->id)->update([
                'photo_path' => $path,
                'updated_at' => now(),
            ]);
            $saved++;
        } catch (\Throwable $exception) {
            $errors++;
            $this->warn("Echec ecriture avatar old_user_id={$oldUserId} (staff_id={$staffRow->id}): " . $exception->getMessage());
        }
    }

    $this->info("Import avatars termine. Sauvegardes={$saved}, ignores={$skipped}, erreurs={$errors}");
})->purpose('Importe les avatars staff depuis le BLOB legacy tbl_users.avatar');
