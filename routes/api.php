<?php

use App\Http\Controllers\Api\V1\Auth\AuthTokenController;
use App\Http\Controllers\Api\V1\Crm\CommuterMessageController;
use App\Http\Controllers\Api\V1\Crm\CommuterThreadController;
use App\Http\Controllers\Api\V1\RouteFinding\CommuterRouteFindingController;
use Illuminate\Support\Facades\Route;

// production test endpoint
Route::get('/api/ping', fn() => response()->json(['ok' => true]));

Route::prefix('v1')->name('api.v1.')->group(function () {

    Route::post('auth/register', [AuthTokenController::class, 'register'])->name('auth.register');
    Route::post('auth/login', [AuthTokenController::class, 'login'])->name('auth.login');

    Route::middleware(['auth:api', 'role.type:commuter'])->group(function () {
        Route::get('auth/me', [AuthTokenController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthTokenController::class, 'destroy'])->name('auth.logout');

        Route::get('crm/threads', [CommuterThreadController::class, 'index'])->name('crm.threads.index');
        Route::post('crm/threads', [CommuterThreadController::class, 'store'])->name('crm.threads.store');
        Route::get('crm/threads/{thread}', [CommuterThreadController::class, 'show'])->name('crm.threads.show');

        Route::get('crm/threads/{thread}/messages', [CommuterMessageController::class, 'index'])->name('crm.threads.messages.index');
        Route::post('crm/threads/{thread}/messages', [CommuterMessageController::class, 'store'])->name('crm.threads.messages.store');

        Route::get('route-finding/nearest-stops', [CommuterRouteFindingController::class, 'nearestStops'])
            ->name('route-finding.nearest-stops');
    });
});
