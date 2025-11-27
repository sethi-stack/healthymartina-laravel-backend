<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Recipes\RecipeController;
use App\Http\Controllers\Api\V1\Recipes\CommentController;
use App\Http\Controllers\Api\V1\Recipes\PdfController as RecipePdfController;
use App\Http\Controllers\Api\V1\Calendars\CalendarController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\Ingredients\IngredientController;
use App\Http\Controllers\Api\V1\Subscriptions\SubscriptionController;
use App\Http\Controllers\Api\V1\LegalDocsController;
use App\Http\Controllers\Api\V1\Auth\VerificationController;

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
        
        // Email verification (public - called from email link)
        Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('api.v1.auth.verify');
    });
    
    // Legal Documents (public)
    Route::prefix('legal')->group(function () {
        Route::get('/terms', [LegalDocsController::class, 'terms'])->name('api.v1.legal.terms');
        Route::get('/privacy', [LegalDocsController::class, 'privacy'])->name('api.v1.legal.privacy');
    });

    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Auth routes
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [LoginController::class, 'destroy'])->name('api.v1.auth.logout');
            Route::get('/user', function (Request $request) {
                return $request->user();
            })->name('api.v1.auth.user');
            
            // Email verification (protected)
            Route::post('/email/resend', [VerificationController::class, 'resend'])->name('api.v1.auth.resend');
            Route::get('/email/status', [VerificationController::class, 'status'])->name('api.v1.auth.status');
        });
        
        // Legal Documents (protected - for acceptance tracking)
        Route::prefix('legal')->group(function () {
            Route::post('/terms/accept', [LegalDocsController::class, 'acceptTerms'])->name('api.v1.legal.terms.accept');
            Route::post('/privacy/accept', [LegalDocsController::class, 'acceptPrivacy'])->name('api.v1.legal.privacy.accept');
        });

        // Recipe routes
        Route::prefix('recipes')->group(function () {
            Route::get('/', [RecipeController::class, 'index'])->name('api.v1.recipes.index');
            Route::get('/search', [RecipeController::class, 'search'])->name('api.v1.recipes.search');
            Route::get('/popular', [RecipeController::class, 'popular'])->name('api.v1.recipes.popular');
            Route::get('/bookmarks', [RecipeController::class, 'bookmarks'])->name('api.v1.recipes.bookmarks');
            
            // Recipe by ID routes (must come before {slug})
            Route::get('/{id}/similar', [RecipeController::class, 'similar'])->where('id', '[0-9]+')->name('api.v1.recipes.similar');
            Route::get('/{id}/stats', [RecipeController::class, 'stats'])->where('id', '[0-9]+')->name('api.v1.recipes.stats');
            Route::post('/{id}/bookmark', [RecipeController::class, 'toggleBookmark'])->where('id', '[0-9]+')->name('api.v1.recipes.bookmark');
            Route::post('/{id}/react', [RecipeController::class, 'react'])->where('id', '[0-9]+')->name('api.v1.recipes.react');
            Route::delete('/{id}/react', [RecipeController::class, 'removeReaction'])->where('id', '[0-9]+')->name('api.v1.recipes.react.remove');
            
            // Comments
            Route::get('/{id}/comments', [CommentController::class, 'index'])->where('id', '[0-9]+')->name('api.v1.recipes.comments.index');
            Route::post('/{id}/comments', [CommentController::class, 'store'])->where('id', '[0-9]+')->name('api.v1.recipes.comments.store');
            Route::delete('/comments/{commentId}', [CommentController::class, 'destroy'])->name('api.v1.recipes.comments.destroy');
            
            // PDF Export
            Route::get('/{id}/pdf', [RecipePdfController::class, 'download'])->where('id', '[0-9]+')->name('api.v1.recipes.pdf');
            Route::post('/{id}/pdf/email', [RecipePdfController::class, 'email'])->where('id', '[0-9]+')->name('api.v1.recipes.pdf.email');
            
            // Recipe by slug (must be last)
            Route::get('/{slug}', [RecipeController::class, 'show'])->name('api.v1.recipes.show');
        });

        // Ingredient routes
        Route::prefix('ingredients')->group(function () {
            Route::get('/', [IngredientController::class, 'index'])->name('api.v1.ingredients.index');
            Route::get('/{id}', [IngredientController::class, 'show'])->name('api.v1.ingredients.show');
            Route::get('/{id}/instrucciones', [IngredientController::class, 'instrucciones'])->name('api.v1.ingredients.instrucciones');
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
        
        // Subscription routes
        Route::prefix('subscriptions')->group(function () {
            Route::get('/plans', [SubscriptionController::class, 'plans'])->name('api.v1.subscriptions.plans');
            Route::get('/stripe-plans', [SubscriptionController::class, 'stripePlans'])->name('api.v1.subscriptions.stripe-plans');
            Route::get('/current', [SubscriptionController::class, 'current'])->name('api.v1.subscriptions.current');
            Route::post('/setup-intent', [SubscriptionController::class, 'setupIntent'])->name('api.v1.subscriptions.setup-intent');
            Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('api.v1.subscriptions.subscribe');
            Route::put('/update-plan', [SubscriptionController::class, 'updatePlan'])->name('api.v1.subscriptions.update-plan');
            Route::post('/cancel', [SubscriptionController::class, 'cancel'])->name('api.v1.subscriptions.cancel');
            Route::post('/resume', [SubscriptionController::class, 'resume'])->name('api.v1.subscriptions.resume');
        });
    });
});
