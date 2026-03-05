<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ComplaintController;
use App\Http\Controllers\Api\V1\ComplaintCategoryController;
use App\Http\Controllers\Api\V1\ComplaintMessageController;

Route::prefix('v1')->group(function () {
    Route::get('/ping', fn () => response()->json(['ok' => true]));

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']); // optional

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::get('/complaint-categories', [ComplaintCategoryController::class, 'index']);

        Route::get('/complaints', [ComplaintController::class, 'index']);
        Route::post('/complaints', [ComplaintController::class, 'store']);
        Route::get('/complaints/{complaint}', [ComplaintController::class, 'show']);

        Route::get('/complaints/{complaint}/messages', [ComplaintMessageController::class, 'index']);
        Route::post('/complaints/{complaint}/messages', [ComplaintMessageController::class, 'store']);
    });
});