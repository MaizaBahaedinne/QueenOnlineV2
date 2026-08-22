<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Salle;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $safeCount = static function (string $table, callable $query): int {
            if (!Schema::hasTable($table)) {
                return 0;
            }

            try {
                return (int) $query();
            } catch (QueryException) {
                return 0;
            }
        };

        $safeSum = static function (string $table, callable $query): float {
            if (!Schema::hasTable($table)) {
                return 0.0;
            }

            try {
                return (float) $query();
            } catch (QueryException) {
                return 0.0;
            }
        };

        $recentReservations = collect();

        if (Schema::hasTable('reservations') && Schema::hasTable('clients') && Schema::hasTable('salles')) {
            try {
                $recentReservations = Reservation::query()
                    ->with(['client', 'salle'])
                    ->latest()
                    ->limit(6)
                    ->get();
            } catch (QueryException) {
                $recentReservations = collect();
            }
        }

        return view('dashboard', [
            'title' => 'Dashboard',
            'stats' => [
                'users' => $safeCount('users', fn () => User::query()->count()),
                'clients' => $safeCount('clients', fn () => Client::query()->count()),
                'salles' => $safeCount('salles', fn () => Salle::query()->count()),
                'reservations' => $safeCount('reservations', fn () => Reservation::query()->count()),
                'payments' => $safeCount('payments', fn () => Payment::query()->count()),
                'payments_total' => $safeSum('payments', fn () => Payment::query()->sum('amount')),
            ],
            'recentReservations' => $recentReservations,
        ]);
    }
}
