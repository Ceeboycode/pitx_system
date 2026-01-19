<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\GateController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleTypeController;

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
    Route::post('/gates/{id}/restore', [GateController::class, 'restore'])->name('gates.restore');
    Route::delete('/gates/{id}/force-delete', [GateController::class, 'forceDelete'])->name('gates.forceDelete');

});


require __DIR__.'/settings.php';
