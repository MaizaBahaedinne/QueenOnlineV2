<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Salle;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'title' => 'Dashboard',
            'stats' => [
                'users' => User::query()->count(),
                'clients' => Client::query()->count(),
                'salles' => Salle::query()->count(),
                'reservations' => Reservation::query()->count(),
                'payments' => Payment::query()->count(),
                'payments_total' => (float) Payment::query()->sum('amount'),
            ],
            'recentReservations' => Reservation::query()
                ->with(['client', 'salle'])
                ->latest()
                ->limit(6)
                ->get(),
        ]);
    }
}
