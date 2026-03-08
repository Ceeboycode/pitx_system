<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyDashboardController;
use App\Http\Controllers\CompanyDocumentController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\CompanyRegistration;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\RouteStopController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleTypeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

/*
|--------------------------------------------------------------------------
| Public — Welcome
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

/*
|--------------------------------------------------------------------------
| Company Registration Wizard
|--------------------------------------------------------------------------
| ✅ GET is public (prevents redirect loops if user is logged in)
| ✅ POST steps are guest-only
*/
Route::get('company-registration', [CompanyRegistration::class, 'show'])
    ->name('company-registration.show');

Route::middleware('guest')->group(function () {
    Route::post('company-registration/step-1', [CompanyRegistration::class, 'storeStep1'])
        ->name('company-registration.storeStep1');

    Route::post('company-registration/step-1/resend-otp', [CompanyRegistration::class, 'resendStep1Otp'])
        ->name('company-registration.resendStep1Otp');

    Route::post('company-registration/step-1/verify-otp', [CompanyRegistration::class, 'verifyStep1Otp'])
        ->name('company-registration.verifyStep1Otp');

    Route::post('company-registration/step-2', [CompanyRegistration::class, 'storeStep2'])
        ->name('company-registration.storeStep2');

    Route::post('company-registration/step-2/resend-otp', [CompanyRegistration::class, 'resendStep2Otp'])
        ->name('company-registration.resendStep2Otp');

    Route::post('company-registration/step-2/verify-otp', [CompanyRegistration::class, 'verifyStep2Otp'])
        ->name('company-registration.verifyStep2Otp');

    Route::post('company-registration/step-3', [CompanyRegistration::class, 'storeStep3'])
        ->name('company-registration.storeStep3');
});

/*
|--------------------------------------------------------------------------
| EXTERNAL (Portal) — Auth + role.type:external
|--------------------------------------------------------------------------
| External users should not access admin modules.
*/
Route::middleware(['auth', 'role.type:external'])->group(function () {

    // Status page is for external users (do NOT put company.verified here)
    Route::get('registration/status', [CompanyRegistration::class, 'status'])
        ->name('registration.status');
    // Route::get('registration/resubmit', [CompanyRegistration::class, 'editResubmission'])
    //     ->name('registration.resubmit');

    Route::post('registration/resubmit', [CompanyRegistration::class, 'storeResubmission'])
        ->name('registration.resubmit.store');
    // Only verified company can access portal dashboard
    Route::middleware('company.verified')->group(function () {
        Route::get('company/dashboard', [CompanyDashboardController::class, 'index'])
            ->name('company.dashboard');

        Route::get('/profile', [CompanyProfileController::class, 'show'])->name('profile');
        Route::post('/profile/logo', [CompanyProfileController::class, 'updateLogo'])->name('profile.logo.update');
        Route::delete('/profile/logo/remove', [CompanyProfileController::class, 'removeLogo'])->name('profile.logo.remove');
    });
});

/*
|--------------------------------------------------------------------------
| INTERNAL (Admin) — Auth + role.type:internal
|--------------------------------------------------------------------------
| Internal users should not access external portal.
*/
Route::middleware(['auth', 'role.type:internal'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | Users & Roles
    |--------------------------------------------------------------------------
    */
    Route::resource('users', UserController::class);
    Route::resource('roles', RoleController::class);
    Route::get('/companies/export', [\App\Http\Controllers\CompanyBackupController::class, 'export'])
        ->name('companies.export');

    Route::post('/companies/import', [\App\Http\Controllers\CompanyBackupController::class, 'import'])
        ->name('companies.import');
    /*
    |--------------------------------------------------------------------------
    | Companies + Document management
    |--------------------------------------------------------------------------
    */
    Route::prefix('companies')
        ->name('companies.')
        ->whereNumber('company')
        ->group(function () {

            Route::get('trash', [CompanyController::class, 'trash'])->name('trash');

            // Document actions
            Route::post('{company}/documents/download-bulk', [CompanyDocumentController::class, 'downloadBulk'])
                ->name('documents.downloadBulk');

            Route::get('{company}/documents/{document}/download', [CompanyDocumentController::class, 'download'])
                ->name('documents.download');

            Route::patch('{company}/documents/{document}/verify', [CompanyDocumentController::class, 'verify'])
                ->name('documents.verify');

            Route::patch('{company}/documents/{document}/unverify', [CompanyDocumentController::class, 'unverify'])
                ->name('documents.unverify');

            Route::patch('{company}/documents/{document}/reject', [CompanyDocumentController::class, 'reject'])
                ->name('documents.reject');

            Route::delete('{company}/documents/{document}', [CompanyDocumentController::class, 'destroy'])
                ->name('documents.destroy');

            // Company CRUD
            Route::get('/', [CompanyController::class, 'index'])->name('index');
            Route::get('create', [CompanyController::class, 'create'])->name('create');
            Route::post('/', [CompanyController::class, 'store'])->name('store');

            Route::get('{company}', [CompanyController::class, 'show'])->name('show');
            Route::get('{company}/edit', [CompanyController::class, 'edit'])->name('edit');
            Route::put('{company}', [CompanyController::class, 'update'])->name('update');
            Route::delete('{company}', [CompanyController::class, 'destroy'])->name('destroy');

            Route::patch('{company}/restore', [CompanyController::class, 'restore'])
                ->withTrashed()
                ->name('restore');

            Route::delete('{company}/force-delete', [CompanyController::class, 'forceDelete'])
                ->withTrashed()
                ->name('forceDelete');
        });

    /*
    |--------------------------------------------------------------------------
    | Vehicle Types
    |--------------------------------------------------------------------------
    */
    Route::resource('vehicle-types', VehicleTypeController::class);

    /*
    |--------------------------------------------------------------------------
    | Gates
    |--------------------------------------------------------------------------
    */
    Route::resource('gates', GateController::class);
    Route::get('gates-trash', [GateController::class, 'trash'])->name('gates.trash');
    Route::post('gates/{gate}/restore', [GateController::class, 'restore'])->name('gates.restore');
    Route::delete('gates/{gate}/force-delete', [GateController::class, 'forceDelete'])->name('gates.forceDelete');

    /*
    |--------------------------------------------------------------------------
    | Route Stops
    |--------------------------------------------------------------------------
    */
    Route::resource('route-stops', RouteStopController::class);
    Route::get('route-stops-trash', [RouteStopController::class, 'trash'])->name('route-stops.trash');
    Route::post('route-stops/{route_stop}/restore', [RouteStopController::class, 'restore'])->name('route-stops.restore');
    Route::delete('route-stops/{route_stop}/force-delete', [RouteStopController::class, 'forceDelete'])->name('route-stops.forceDelete');

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    Route::resource('routes', RouteController::class);
    Route::get('routes-trash', [RouteController::class, 'trash'])->name('routes.trash');
    Route::patch('routes/{route}/restore', [RouteController::class, 'restore'])->withTrashed()->name('routes.restore');
    Route::delete('routes/{route}/force-delete', [RouteController::class, 'forceDelete'])->withTrashed()->name('routes.forceDelete');
    Route::patch('/{route}/toggle-status', [RouteController::class, 'toggleStatus'])->name('toggleStatus');

    /*
    |--------------------------------------------------------------------------
    | Vehicles
    |--------------------------------------------------------------------------
    */
    Route::resource('vehicles', VehicleController::class);
    Route::get('vehicles-trash', [VehicleController::class, 'trash'])->name('vehicles.trash');
    Route::post('vehicles/{vehicle}/restore', [VehicleController::class, 'restore'])->withTrashed()->name('vehicles.restore');
    Route::delete('vehicles/{vehicle}/force-delete', [VehicleController::class, 'forceDelete'])->withTrashed()->name('vehicles.forceDelete');

    /*
    |--------------------------------------------------------------------------
    | Admin Dispatches
    |--------------------------------------------------------------------------
    */
    Route::resource('dispatches', DispatchController::class)->except(['create', 'store']);
    Route::get('dispatches/create/{company}', [DispatchController::class, 'create'])->name('dispatches.create');
    Route::post('dispatches/{company}', [DispatchController::class, 'store'])->name('dispatches.store');

    /*
    |--------------------------------------------------------------------------
    | Misc
    |--------------------------------------------------------------------------
    */
    Route::get('faq', fn () => Inertia::render('FAQ'))->name('faq');
});

require __DIR__ . '/settings.php';
