<?php

use App\Http\Controllers\Api\ServiceJobController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Support\Facades\Route;

/*
| JobDesk REST API (all responses are JSON).
| 1) POST /api/tokens  {email, password, device_name}  → Bearer token
| 2) Send it as:  Authorization: Bearer <token>
*/

Route::name('api.')->group(function () {
    Route::post('/tokens', [TokenController::class, 'store'])
        ->middleware('throttle:6,1')        // 6 attempts per minute
        ->name('tokens.store');

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::delete('/tokens/current', [TokenController::class, 'destroy'])->name('tokens.destroy');

        Route::middleware('abilities:jobs:read')->group(function () {
            Route::get('/jobs', [ServiceJobController::class, 'index'])->name('jobs.index');
            Route::get('/jobs/{job}', [ServiceJobController::class, 'show'])->name('jobs.show');
        });

        Route::middleware('abilities:jobs:write')->group(function () {
            Route::post('/jobs', [ServiceJobController::class, 'store'])->name('jobs.store');
            Route::patch('/jobs/{job}/status', [ServiceJobController::class, 'updateStatus'])->name('jobs.status');
        });
    });
});
