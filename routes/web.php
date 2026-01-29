<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\GateController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\RouteStopController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleTypeController;
use App\Http\Controllers\VehicleController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);

    Route::resource('companies', CompanyController::class);
    Route::get('/companies-trash', [CompanyController::class, 'trash'])->name('companies.trash');
    Route::post('/companies/{id}/restore', [CompanyController::class, 'restore'])->name('companies.restore');
    Route::delete('/companies/{id}/force-delete', [CompanyController::class, 'forceDelete'])->name('companies.forceDelete');


    Route::resource('vehicle-types', VehicleTypeController::class);

    Route::resource('gates', GateController::class);
    Route::get('/gates-trash', [GateController::class, 'trash'])->name('gates.trash');
    Route::post('/gates/{gate}/restore', [GateController::class, 'restore'])
        ->name('gates.restore');
    Route::delete('/gates/{gate}/force-delete', [GateController::class, 'forceDelete'])
        ->name('gates.forceDelete');

    Route::resource('route-stops', RouteStopController::class);
    Route::get('/route-stops-trash', [RouteStopController::class, 'trash'])->name('route-stops.trash');
    Route::post('/route-stops/{route_stop}/restore', [RouteStopController::class, 'restore'])
        ->name('route-stops.restore');
    Route::delete('/route-stops/{route_stop}/force-delete', [RouteStopController::class, 'forceDelete'])
        ->name('route-stops.forceDelete');

    Route::resource('routes', RouteController::class);
    Route::get('/routes-trash', [RouteController::class, 'trash'])->name('routes.trash');
    Route::post('/routes/{route}/restore', [RouteController::class, 'restore'])
        ->withTrashed()
        ->name('routes.restore');
    Route::delete('/routes/{route}/force-delete', [RouteController::class, 'forceDelete'])
        ->withTrashed()
        ->name('routes.forceDelete');



    Route::resource('vehicles', VehicleController::class);
});


require __DIR__.'/settings.php';
