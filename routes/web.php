<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MigrationMappingController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceModuleController;
use App\Http\Controllers\SalleController;
use App\Http\Controllers\SalleOptionController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ModuleController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

Route::middleware('auth')->group(function () {
	Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
	Route::get('/profile/password', [ProfileController::class, 'editPassword'])->name('profile.password.edit');
	Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
	Route::get('clients/cin-check', [ClientController::class, 'checkCin'])->name('clients.cin-check');

	Route::resource('users', UserController::class);
	Route::resource('clients', ClientController::class);
	Route::post('clients/{client}/transfer-credit', [ClientController::class, 'transferCredit'])->name('clients.transfer-credit');
	Route::post('staff/{staff}/documents', [StaffController::class, 'storeDocument'])->name('staff.documents.store');
	Route::delete('staff/{staff}/documents/{document}', [StaffController::class, 'destroyDocument'])->name('staff.documents.destroy');
	Route::resource('staff', StaffController::class);
	Route::resource('salles', SalleController::class);
	Route::get('salles/{salle}/options', [SalleOptionController::class, 'index'])->name('salles.options.index');
	Route::post('salles/{salle}/options', [SalleOptionController::class, 'store'])->name('salles.options.store');
	Route::patch('salles/{salle}/options/{option}', [SalleOptionController::class, 'update'])->name('salles.options.update');
	Route::delete('salles/{salle}/options/{option}', [SalleOptionController::class, 'destroy'])->name('salles.options.destroy');
	Route::get('reservations/availability', [ReservationController::class, 'availableSalles'])->name('reservations.availability');
	Route::get('reservations/{reservation}/available-salles', [ReservationController::class, 'availableSallesForReservation'])->name('reservations.available-salles');
	Route::get('reservations/clients/search', [ReservationController::class, 'searchClients'])->name('reservations.clients.search');
	Route::post('reservations/clients/quick-store', [ReservationController::class, 'quickStoreClient'])->name('reservations.clients.quick-store');
	Route::patch('reservations/{reservation}/client', [ReservationController::class, 'updateClient'])->name('reservations.client.update');
	Route::patch('reservations/{reservation}/details', [ReservationController::class, 'updateDetails'])->name('reservations.details.update');
	Route::patch('reservations/{reservation}/slot', [ReservationController::class, 'updateSlot'])->name('reservations.slot.update');
	Route::patch('reservations/{reservation}/staff-affectations', [ReservationController::class, 'updateStaffAffectations'])->name('reservations.staff-affectations.update');
	Route::get('reservations/{reservation}/contract', [ReservationController::class, 'contract'])->name('reservations.contract');
	Route::get('reservations/{reservation}/invoice', [ReservationController::class, 'invoice'])->name('reservations.invoice');
	Route::patch('reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');
	Route::post('reservations/{reservation}/clone', [ReservationController::class, 'cloneReservation'])->name('reservations.clone');
	Route::post('reservations/{reservation}/apply-credit', [ReservationController::class, 'applyClientCredit'])->name('reservations.apply-credit');
	Route::post('reservations/{reservation}/transfer-credit', [ReservationController::class, 'transferClientCredit'])->name('reservations.transfer-credit');
	Route::patch('reservations/{reservation}/salle', [ReservationController::class, 'updateSalle'])->name('reservations.salle.update');
	Route::post('reservations/{reservation}/payments', [ReservationController::class, 'storePayment'])->name('reservations.payments.store');
	Route::post('reservations/{reservation}/additional-services', [ReservationController::class, 'storeAdditionalService'])->name('reservations.additional-services.store');
	Route::delete('reservations/{reservation}/additional-services/{additionalService}', [ReservationController::class, 'destroyAdditionalService'])->name('reservations.additional-services.destroy');
	Route::patch('reservations/{reservation}/additional-services/{additionalService}/start-time', [ReservationController::class, 'updateAdditionalServiceStartTime'])->name('reservations.additional-services.start-time.update');
	Route::get('reservations/{reservation}/service-inventory', [ReservationController::class, 'serviceInventory'])->name('reservations.service-inventory');
	Route::post('reservations/{reservation}/service-entrees', [ReservationController::class, 'storeServiceEntree'])->name('reservations.service-entrees.store');
	Route::post('reservations/{reservation}/service-entrees/{serviceEntree}/increment', [ReservationController::class, 'incrementServiceEntreeQuantity'])->name('reservations.service-entrees.increment');
	Route::post('reservations/{reservation}/service-entrees/{serviceEntree}/retours', [ReservationController::class, 'storeServiceRetour'])->name('reservations.service-retours.store');
	Route::post('reservations/{reservation}/service-feedbacks', [ReservationController::class, 'storeServiceFeedback'])->name('reservations.service-feedbacks.store');
	Route::post('reservations/{reservation}/salle-options', [ReservationController::class, 'storeSalleOption'])->name('reservations.salle-options.store');
	Route::delete('reservations/{reservation}/salle-options/{salleOptionRow}', [ReservationController::class, 'destroySalleOption'])->name('reservations.salle-options.destroy');
	Route::resource('reservations', ReservationController::class);
	Route::resource('payments', PaymentController::class);

	Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
	Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
	Route::patch('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
	Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

	Route::get('modules', [ModuleController::class, 'index'])->name('modules.index');
	Route::post('modules', [ModuleController::class, 'storeModule'])->name('modules.store');
	Route::post('modules/{module}/features', [ModuleController::class, 'storeFeature'])->name('modules.features.store');
	Route::patch('modules/{module}/toggle', [ModuleController::class, 'toggleModule'])->name('modules.toggle');
	Route::patch('module-features/{feature}/toggle', [ModuleController::class, 'toggleFeature'])->name('modules.features.toggle');

	Route::get('migration-mappings', [MigrationMappingController::class, 'index'])->name('migration-mappings.index');
	Route::post('migration-mappings', [MigrationMappingController::class, 'store'])->name('migration-mappings.store');
	Route::patch('migration-mappings/{migrationMapping}', [MigrationMappingController::class, 'update'])->name('migration-mappings.update');
	Route::delete('migration-mappings/{migrationMapping}', [MigrationMappingController::class, 'destroy'])->name('migration-mappings.destroy');
	Route::get('migration-mappings/export/file', [MigrationMappingController::class, 'export'])->name('migration-mappings.export');
	Route::get('migration-mappings/schema/tables', [MigrationMappingController::class, 'schemaTables'])->name('migration-mappings.schema.tables');
	Route::get('migration-mappings/schema/columns', [MigrationMappingController::class, 'schemaColumns'])->name('migration-mappings.schema.columns');

	Route::get('permissions/matrix', [RolePermissionController::class, 'index'])->name('permissions.matrix');
	Route::post('permissions/matrix', [RolePermissionController::class, 'update'])->name('permissions.matrix.update');

	Route::get('service-modules/{module}', [ServiceModuleController::class, 'show'])->name('service-modules.show');
	Route::get('service-modules/{module}/packs', [ServiceModuleController::class, 'showPacks'])->name('service-modules.packs.index');
	Route::post('service-modules/{module}/items', [ServiceModuleController::class, 'storeItem'])->name('service-modules.items.store');
	Route::patch('service-modules/{module}/items/{item}', [ServiceModuleController::class, 'updateItem'])->name('service-modules.items.update');
	Route::patch('service-modules/{module}/items/{item}/partnership-prices', [ServiceModuleController::class, 'updatePartnershipPrices'])->name('service-modules.items.partnership-prices.update');
	Route::delete('service-modules/{module}/items/{item}', [ServiceModuleController::class, 'destroyItem'])->name('service-modules.items.destroy');

	Route::post('service-modules/{module}/packs', [ServiceModuleController::class, 'storePack'])->name('service-modules.packs.store');
	Route::patch('service-modules/{module}/packs/{pack}', [ServiceModuleController::class, 'updatePack'])->name('service-modules.packs.update');
	Route::delete('service-modules/{module}/packs/{pack}', [ServiceModuleController::class, 'destroyPack'])->name('service-modules.packs.destroy');
});
