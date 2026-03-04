<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DispatchController;
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
use App\Http\Controllers\CompanyDocumentController;
use App\Http\Controllers\CompanyRegistration;


Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/company-registration', [CompanyRegistration::class, 'show'])
    ->name('company-registration.show');




Route::middleware('auth')->group(function () {
    Route::resource('users', UserController::class);

    Route::resource('roles', RoleController::class);

    Route::prefix('companies')
        ->name('companies.')
        ->whereNumber('company')
        ->group(function () {

            Route::get('trash', [CompanyController::class, 'trash'])->name('trash');

            Route::get('{company}/documents/{document}/download', [CompanyDocumentController::class, 'download'])
                ->name('documents.download');

            Route::patch('{company}/documents/{document}/unverify', [CompanyDocumentController::class, 'unverify'])
                ->name('documents.unverify');

            //  Document actions
            Route::patch('{company}/documents/{document}/verify', [CompanyDocumentController::class, 'verify'])
                ->name('documents.verify');

            Route::patch('{company}/documents/{document}/reject', [CompanyDocumentController::class, 'reject'])
                ->name('documents.reject');

            Route::delete('{company}/documents/{document}', [CompanyDocumentController::class, 'destroy'])
                ->name('documents.destroy');

            Route::get('/', [CompanyController::class, 'index'])->name('index');
            Route::get('create', [CompanyController::class, 'create'])->name('create');
            Route::post('/', [CompanyController::class, 'store'])->name('store');

            Route::get('{company}/edit', [CompanyController::class, 'edit'])->name('edit');

            Route::get('{company}', [CompanyController::class, 'show'])->name('show');
            Route::put('{company}', [CompanyController::class, 'update'])->name('update');
            Route::delete('{company}', [CompanyController::class, 'destroy'])->name('destroy');

            Route::patch('{company}/restore', [CompanyController::class, 'restore'])
                ->withTrashed()
                ->name('restore');

            Route::delete('{company}/force-delete', [CompanyController::class, 'forceDelete'])
                ->withTrashed()
                ->name('forceDelete');
        });

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
    Route::get('/vehicles-trash', [VehicleController::class, 'trash'])->name('vehicles.trash');
    Route::post('/vehicles/{vehicle}/restore', [VehicleController::class, 'restore'])
        ->withTrashed()
        ->name('vehicles.restore');
    Route::delete('/vehicles/{vehicle}/force-delete', [VehicleController::class, 'forceDelete'])
        ->withTrashed()
        ->name('vehicles.forceDelete');

    Route::get('/faq', function () {
        return Inertia::render('FAQ');
    })->name('faq');

    Route::resource('dispatches', DispatchController::class)->except(['create', 'store']);

    Route::get('/dispatches/create/{company}', [DispatchController::class, 'create'])
        ->name('dispatches.create');

    Route::post('/dispatches/{company}', [DispatchController::class, 'store'])
    ->name('dispatches.store');
});


require __DIR__.'/settings.php';
