<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

abstract class MatrixAwareController extends Controller
{
    protected function enforcePermission(string $moduleSlug, string $featureSlug, string $action): void
    {
        $user = Auth::user();

        abort_unless($user instanceof User && $user->canFeature($moduleSlug, $featureSlug, $action), 403, 'Action non autorisee par la matrice.');
    }
}
