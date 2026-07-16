<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyDashboardController;
use App\Http\Controllers\Messaging\MessagingController;
use App\Http\Controllers\CompanyDocumentController;
use App\Http\Controllers\CompanyProfileChangeRequestController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\CompanyRegistration;
use App\Http\Controllers\CompanyUserController;
use App\Http\Controllers\CompanyVehicleController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Crm\CrmMessageAttachmentController;
use App\Http\Controllers\Crm\CrmMessageController;
use App\Http\Controllers\Crm\CrmThreadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DispatchChangeRequestController;
use App\Http\Controllers\DispatchController;
use App\Http\Controllers\ForcePasswordController;
use App\Http\Controllers\GateController;
use App\Http\Controllers\InternalDispatchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\RouteStopController;
use App\Http\Controllers\Settings\PasswordController as UserSettingsPasswordController;
use App\Http\Controllers\Settings\ProfileController as UserSettingsProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleBackupController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleTypeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('privacy', fn () => Inertia::render('Privacy'))->name('privacy');
Route::get('terms', fn () => Inertia::render('Terms'))->name('terms');
Route::get('contact', fn () => Inertia::render('Contact'))->name('contact');

Route::get('/privacy', function () {
    return Inertia::render('PrivacyPolicy');
})->name('privacy');

Route::get('/terms', function () {
    return Inertia::render('TermsAndConditions');
})->name('terms');
/*
|--------------------------------------------------------------------------
| Company Registration
|--------------------------------------------------------------------------
*/

Route::get('company-registration', [CompanyRegistration::class, 'show'])
    ->name('company-registration.show');

Route::middleware('guest')->prefix('company-registration')->name('company-registration.')->group(function () {
    Route::post('step-1', [CompanyRegistration::class, 'storeStep1'])
        ->name('storeStep1');

    Route::post('step-1/resend-otp', [CompanyRegistration::class, 'resendStep1Otp'])
        ->name('resendStep1Otp');

    Route::post('step-1/verify-otp', [CompanyRegistration::class, 'verifyStep1Otp'])
        ->name('verifyStep1Otp');

    Route::post('step-2', [CompanyRegistration::class, 'storeStep2'])
        ->name('storeStep2');

    Route::post('step-2/resend-otp', [CompanyRegistration::class, 'resendStep2Otp'])
        ->name('resendStep2Otp');

    Route::post('step-2/verify-otp', [CompanyRegistration::class, 'verifyStep2Otp'])
        ->name('verifyStep2Otp');

    Route::post('step-3', [CompanyRegistration::class, 'storeStep3'])
        ->name('storeStep3');
});

/*
|--------------------------------------------------------------------------
| Authenticated shared routes
|--------------------------------------------------------------------------
|
| These routes must stay accessible even when must_change_password = true.
|
*/

Route::middleware(['auth', 'audit.request'])->group(function () {
    Route::get('force-password-reset', [ForcePasswordController::class, 'edit'])
        ->name('force-password.edit');

    Route::post('force-password-reset', [ForcePasswordController::class, 'update'])
        ->name('force-password.update');

    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->name('notifications.read');

    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->name('notifications.read-all');

    /*
    |--------------------------------------------------------------------------
    | Messaging (internal ↔ internal, external ↔ external company)
    |--------------------------------------------------------------------------
    */

    Route::prefix('messaging')->name('messaging.')->middleware('password.change.required')->group(function () {
        Route::get('threads', [MessagingController::class, 'index'])->name('threads.index');
        Route::post('threads', [MessagingController::class, 'store'])->name('threads.store');
        Route::get('threads/{thread}/messages', [MessagingController::class, 'messages'])->name('threads.messages');
        Route::post('threads/{thread}/messages', [MessagingController::class, 'send'])->name('threads.send');
    });
});

/*
|--------------------------------------------------------------------------
| External
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role.type:external', 'audit.request'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Registration / Onboarding Status
    |--------------------------------------------------------------------------
    */

    Route::get('registration/status', [CompanyRegistration::class, 'status'])
        ->name('registration.status');

    Route::post('registration/resubmit', [CompanyRegistration::class, 'storeResubmission'])
        ->name('registration.resubmit.store');

    /*
    |--------------------------------------------------------------------------
    | Protected External Area
    |--------------------------------------------------------------------------
    */
    Route::middleware(['company.verified', 'password.change.required'])->group(function () {
        Route::get('company/dashboard', [CompanyDashboardController::class, 'index'])
            ->name('company.dashboard');

        Route::prefix('company/settings')->name('company.settings.')->group(function () {
            Route::get('/', function () {
                return redirect()->route('company.settings.profile.edit');
            })->name('index');

            Route::get('profile', [UserSettingsProfileController::class, 'externalEdit'])
                ->name('profile.edit');

            Route::patch('profile', [UserSettingsProfileController::class, 'externalUpdate'])
                ->name('profile.update');

            Route::get('password', [UserSettingsPasswordController::class, 'externalEdit'])
                ->name('password.edit');

            Route::put('password', [UserSettingsPasswordController::class, 'externalUpdate'])
                ->middleware('throttle:6,1')
                ->name('password.update');

            Route::get('appearance', function () {
                return Inertia::render('External/Settings/Appearance');
            })->name('appearance.edit');
        });

        /*
        |--------------------------------------------------------------------------
        | Profile
        |--------------------------------------------------------------------------
        */

        Route::prefix('profile')->group(function () {
            Route::get('/', [CompanyProfileController::class, 'show'])->name('profile');
            Route::put('/', [CompanyProfileController::class, 'submitUpdate'])->name('profile.update.submit');
            Route::post('logo', [CompanyProfileController::class, 'submitUpdate'])->name('profile.logo.update');
            Route::delete('logo/remove', [CompanyProfileController::class, 'removeLogo'])->name('profile.logo.remove');
        });

        /*
        |--------------------------------------------------------------------------
        | Company Vehicles
        |--------------------------------------------------------------------------
        */

        Route::prefix('company/vehicles')->name('company.vehicles.')->group(function () {
            Route::get('/', [CompanyVehicleController::class, 'index'])->name('index');
            Route::get('create', [CompanyVehicleController::class, 'create'])->name('create');
            Route::post('/', [CompanyVehicleController::class, 'store'])->name('store');
            Route::get('{vehicle}', [CompanyVehicleController::class, 'show'])->name('show');
            Route::get('{vehicle}/edit', [CompanyVehicleController::class, 'edit'])->name('edit');
            Route::put('{vehicle}', [CompanyVehicleController::class, 'update'])->name('update');

            Route::get('{vehicle}/documents/{document}/download', [CompanyVehicleController::class, 'downloadDocument'])
                ->name('documents.download');

            Route::patch('{vehicle}/toggle-status', [CompanyVehicleController::class, 'toggleStatus'])
                ->name('toggle-status');
        });

        /*
        |--------------------------------------------------------------------------
        | Employee Users
        |--------------------------------------------------------------------------
        */

        Route::resource('employee-users', CompanyUserController::class)
            ->parameters([
                'employee-users' => 'employeeUser',
            ]);

        Route::patch('employee-users/{employeeUser}/toggle-status', [CompanyUserController::class, 'toggleStatus'])
            ->name('employee-users.toggle-status');

        Route::patch('employee-users/{employeeUser}/reset-password', [CompanyUserController::class, 'resetPassword'])
            ->name('employee-users.reset-password');

        Route::get('company/activity-logs', [AuditLogController::class, 'externalMyActivity'])
            ->name('company.activity-logs.index');

        /*
        |--------------------------------------------------------------------------
        | Dispatching
        |--------------------------------------------------------------------------
        */

        Route::get('company/dispatches', [DispatchController::class, 'index'])
            ->name('company.dispatches.index');

        Route::get('company/dispatches/{dispatch}', [DispatchController::class, 'show'])
            ->name('company.dispatches.show');

        Route::post('company/dispatches', [DispatchController::class, 'store'])
            ->name('company.dispatches.store');

        Route::put('company/dispatches/{dispatch}', [DispatchController::class, 'update'])
            ->name('company.dispatches.update');

        Route::patch('company/dispatches/{dispatch}/depart', [DispatchController::class, 'depart'])
            ->name('company.dispatches.depart');

        /*
        |--------------------------------------------------------------------------
        | Dispatch Change Requests
        |--------------------------------------------------------------------------
        */

        Route::post('company/dispatches/{dispatch}/change-requests', [DispatchChangeRequestController::class, 'store'])
            ->name('company.dispatches.change-requests.store');
    });
});

/*
|--------------------------------------------------------------------------
| Internal
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role.type:internal', 'password.change.required', 'audit.request'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/my-activity-logs', [AuditLogController::class, 'myActivity'])->name('activity-logs.index');

    /*
    |--------------------------------------------------------------------------
    | CRM
    |--------------------------------------------------------------------------
    */

    Route::prefix('crm')->name('crm.')->group(function () {
        Route::get('threads', [CrmThreadController::class, 'index'])->name('threads.index');
        Route::get('threads/{thread}', [CrmThreadController::class, 'show'])->name('threads.show');
        Route::post('threads', [CrmThreadController::class, 'store'])->name('threads.store');
        Route::patch('threads/{thread}', [CrmThreadController::class, 'update'])->name('threads.update');
        Route::patch('threads/{thread}/close', [CrmThreadController::class, 'close'])->name('threads.close');
        Route::patch('threads/{thread}/reopen', [CrmThreadController::class, 'reopen'])->name('threads.reopen');

        Route::post('threads/{thread}/messages', [CrmMessageController::class, 'store'])
            ->name('threads.messages.store');

        Route::post('threads/{thread}/messages/{message}/attachments', [CrmMessageAttachmentController::class, 'store'])
            ->name('threads.messages.attachments.store');

        Route::get('attachments/{attachment}/download', [CrmMessageAttachmentController::class, 'download'])
            ->name('attachments.download');
    });

    /*
    |--------------------------------------------------------------------------
    | Users / Roles
    |--------------------------------------------------------------------------
    */

    Route::resource('users', UserController::class);

    Route::get('users-trash', [UserController::class, 'trash'])
        ->name('users.trash');

    Route::patch('users/{user}/restore', [UserController::class, 'restore'])
        ->withTrashed()
        ->name('users.restore');

    Route::put('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
        ->name('users.toggle-status');

    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->name('users.reset-password');

    Route::resource('roles', RoleController::class);

    Route::get('roles-trash', [RoleController::class, 'trash'])
        ->name('roles.trash');

    Route::patch('roles/{role}/restore', [RoleController::class, 'restore'])
        ->withTrashed()
        ->name('roles.restore');

    Route::prefix('company-profile-change-requests')->name('company-profile-change-requests.')->group(function () {
        Route::get('/', [CompanyProfileChangeRequestController::class, 'index'])->name('index');
        Route::post('{changeRequest}/approve', [CompanyProfileChangeRequestController::class, 'approve'])->name('approve');
        Route::post('{changeRequest}/reject', [CompanyProfileChangeRequestController::class, 'reject'])->name('reject');
    });

    /*
    |--------------------------------------------------------------------------
    | Companies Backup
    |--------------------------------------------------------------------------
    */

    Route::get('companies/export', [\App\Http\Controllers\CompanyBackupController::class, 'export'])
        ->name('companies.export');

    Route::post('companies/import', [\App\Http\Controllers\CompanyBackupController::class, 'import'])
        ->name('companies.import');

    /*
    |--------------------------------------------------------------------------
    | Companies
    |--------------------------------------------------------------------------
    */

    Route::prefix('companies')->name('companies.')->group(function () {
        Route::get('trash', [CompanyController::class, 'trash'])->name('trash');

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

    Route::resource('vehicle-types', VehicleTypeController::class);

    Route::resource('gates', GateController::class);
    Route::patch('gates/{gate}/toggle-status', [GateController::class, 'toggleStatus'])->name('gates.toggleStatus');
    Route::get('gates-trash', [GateController::class, 'trash'])->name('gates.trash');
    Route::post('gates/{gate}/restore', [GateController::class, 'restore'])->name('gates.restore');
    Route::delete('gates/{gate}/force-delete', [GateController::class, 'forceDelete'])->name('gates.forceDelete');

    Route::resource('route-stops', RouteStopController::class);
    Route::get('route-stops-trash', [RouteStopController::class, 'trash'])->name('route-stops.trash');
    Route::post('route-stops/{route_stop}/restore', [RouteStopController::class, 'restore'])->name('route-stops.restore');
    Route::delete('route-stops/{route_stop}/force-delete', [RouteStopController::class, 'forceDelete'])->name('route-stops.forceDelete');

    Route::resource('routes', RouteController::class);
    Route::get('routes-trash', [RouteController::class, 'trash'])->name('routes.trash');
    Route::patch('routes/{route}/restore', [RouteController::class, 'restore'])->withTrashed()->name('routes.restore');
    Route::delete('routes/{route}/force-delete', [RouteController::class, 'forceDelete'])->withTrashed()->name('routes.forceDelete');
    Route::patch('routes/{route}/toggle-status', [RouteController::class, 'toggleStatus'])->name('routes.toggleStatus');

    Route::get('vehicles/export', [VehicleBackupController::class, 'export'])
        ->name('vehicles.export');

    Route::post('vehicles/import', [VehicleBackupController::class, 'import'])
        ->name('vehicles.import');

    Route::resource('vehicles', VehicleController::class);

    Route::patch('vehicles/{vehicle}/documents/{document}/verify', [VehicleController::class, 'verifyDocument'])
        ->name('vehicles.documents.verify');

    Route::patch('vehicles/{vehicle}/documents/{document}/invalidate', [VehicleController::class, 'invalidateDocument'])
        ->name('vehicles.documents.invalidate');

    Route::patch('vehicles/{vehicle}/documents/{document}/unverify', [VehicleController::class, 'unverifyDocument'])
        ->name('vehicles.documents.unverify');

    Route::patch('vehicles/{vehicle}/toggle-status', [VehicleController::class, 'toggleStatus'])
        ->name('vehicles.toggle-status');

    Route::get('vehicles-trash', [VehicleController::class, 'trash'])->name('vehicles.trash');
    Route::post('vehicles/{vehicle}/restore', [VehicleController::class, 'restore'])->withTrashed()->name('vehicles.restore');
    Route::delete('vehicles/{vehicle}/force-delete', [VehicleController::class, 'forceDelete'])->withTrashed()->name('vehicles.forceDelete');

    Route::get('dispatches/{dispatch}/export', [InternalDispatchController::class, 'export'])
        ->name('dispatches.export');

    Route::resource('dispatches', InternalDispatchController::class);

    Route::get('dispatch-change-requests', [DispatchChangeRequestController::class, 'index'])
        ->name('dispatch-change-requests.index');

    Route::post('dispatch-change-requests/{changeRequest}/approve', [DispatchChangeRequestController::class, 'approve'])
        ->name('dispatch-change-requests.approve');

    Route::post('dispatch-change-requests/{changeRequest}/reject', [DispatchChangeRequestController::class, 'reject'])
        ->name('dispatch-change-requests.reject');

    Route::get('faq', fn () => Inertia::render('FAQ'))->name('faq');
});

require __DIR__ . '/settings.php';
