<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Recipes\RecipeController;
use App\Http\Controllers\Api\V1\Calendars\CalendarController;
use App\Http\Controllers\Api\V1\User\ProfileController;

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

        // Recipe routes
        Route::prefix('recipes')->group(function () {
            Route::get('/', [RecipeController::class, 'index'])->name('api.v1.recipes.index');
            Route::get('/search', [RecipeController::class, 'search'])->name('api.v1.recipes.search');
            Route::get('/{slug}', [RecipeController::class, 'show'])->name('api.v1.recipes.show');
        });
        
        // Calendar routes
        Route::prefix('calendars')->group(function () {
            Route::get('/', [CalendarController::class, 'index'])->name('api.v1.calendars.index');
            Route::post('/', [CalendarController::class, 'store'])->name('api.v1.calendars.store');
            Route::get('/{id}', [CalendarController::class, 'show'])->name('api.v1.calendars.show');
            Route::put('/{id}', [CalendarController::class, 'update'])->name('api.v1.calendars.update');
            Route::delete('/{id}', [CalendarController::class, 'destroy'])->name('api.v1.calendars.destroy');
            Route::post('/{id}/copy', [CalendarController::class, 'copy'])->name('api.v1.calendars.copy');
        });
        
        // User Profile routes
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show'])->name('api.v1.profile.show');
            Route::put('/', [ProfileController::class, 'update'])->name('api.v1.profile.update');
            Route::put('/password', [ProfileController::class, 'updatePassword'])->name('api.v1.profile.password');
            Route::post('/photo', [ProfileController::class, 'uploadPhoto'])->name('api.v1.profile.photo');
            Route::delete('/', [ProfileController::class, 'destroy'])->name('api.v1.profile.destroy');
        });
        
        // Subscription routes will be added here
    });
});
