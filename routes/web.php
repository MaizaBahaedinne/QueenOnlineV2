<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SalleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::resource('users', UserController::class);
Route::resource('clients', ClientController::class);
Route::resource('salles', SalleController::class);
Route::resource('reservations', ReservationController::class);
Route::resource('payments', PaymentController::class);
