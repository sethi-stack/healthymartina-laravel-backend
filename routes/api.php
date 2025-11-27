<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    
    // Public routes (Authentication)
    Route::prefix('auth')->group(function () {
        Route::post('/login', [LoginController::class, 'store'])->name('api.v1.auth.login');
        Route::post('/register', [RegisterController::class, 'store'])->name('api.v1.auth.register');
    });

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth routes
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [LoginController::class, 'destroy'])->name('api.v1.auth.logout');
            Route::get('/user', function (Request $request) {
                return $request->user();
            })->name('api.v1.auth.user');
        });

        // Recipe routes will be added here
        
        // Calendar routes will be added here
        
        // User routes will be added here
        
        // Subscription routes will be added here
    });
});
