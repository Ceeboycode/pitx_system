<?php

use App\Http\Controllers\Api\V1\Auth\AuthTokenController;
use App\Http\Controllers\Api\V1\Crm\CommuterMessageController;
use App\Http\Controllers\Api\V1\Crm\CommuterThreadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/login', [AuthTokenController::class, 'store'])->name('auth.login');

    Route::middleware(['auth:api', 'role.type:commuter'])->group(function () {
        Route::get('auth/me', [AuthTokenController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthTokenController::class, 'destroy'])->name('auth.logout');

        Route::get('crm/threads', [CommuterThreadController::class, 'index'])->name('crm.threads.index');
        Route::post('crm/threads', [CommuterThreadController::class, 'store'])->name('crm.threads.store');
        Route::get('crm/threads/{thread}', [CommuterThreadController::class, 'show'])->name('crm.threads.show');

        Route::get('crm/threads/{thread}/messages', [CommuterMessageController::class, 'index'])->name('crm.threads.messages.index');
        Route::post('crm/threads/{thread}/messages', [CommuterMessageController::class, 'store'])->name('crm.threads.messages.store');
    });
});
