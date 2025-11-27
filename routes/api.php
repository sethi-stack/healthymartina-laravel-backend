<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\Recipes\RecipeController;
use App\Http\Controllers\Api\V1\Recipes\CommentController;
use App\Http\Controllers\Api\V1\Recipes\PdfController as RecipePdfController;
use App\Http\Controllers\Api\V1\Calendars\CalendarController;
use App\Http\Controllers\Api\V1\Calendars\ListaController;
use App\Http\Controllers\Api\V1\Calendars\ListaPdfController;
use App\Http\Controllers\Api\V1\Plans\MealPlanController;
use App\Http\Controllers\Api\V1\Plans\MealPlanPdfController;
use App\Http\Controllers\Api\V1\User\ProfileController;
use App\Http\Controllers\Api\V1\Ingredients\IngredientController;
use App\Http\Controllers\Api\V1\Subscriptions\SubscriptionController;
use App\Http\Controllers\Api\V1\LegalDocsController;
use App\Http\Controllers\Api\V1\Auth\VerificationController;
use App\Http\Controllers\Api\V1\Filters\FilterBookmarkController;

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
            
            // Advanced filtering and metadata
            Route::post('/advanced-filter', [RecipeController::class, 'advancedFilter'])->name('api.v1.recipes.advanced-filter');
            Route::get('/filter-metadata', [RecipeController::class, 'filterMetadata'])->name('api.v1.recipes.filter-metadata');
            Route::post('/{id}/track-view', [RecipeController::class, 'trackView'])->where('id', '[0-9]+')->name('api.v1.recipes.track-view');
            
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
            
            // Calendar schedules
            Route::get('/schedules', [CalendarController::class, 'schedules'])->name('api.v1.calendars.schedules');
            
            // Lista de Ingredientes (Calendar ingredient lists)
            Route::prefix('{calendarId}/lista')->group(function () {
                // Get lista
                Route::get('/', [ListaController::class, 'index'])->name('api.v1.calendars.lista.index');
                
                // Get by category
                Route::get('/categories/{categoryId}', [ListaController::class, 'category'])->name('api.v1.calendars.lista.category');
                
                // Toggle taken
                Route::post('/toggle-taken', [ListaController::class, 'toggleTaken'])->name('api.v1.calendars.lista.toggle');
                
                // Custom items CRUD
                Route::post('/items', [ListaController::class, 'storeCustom'])->name('api.v1.calendars.lista.items.store');
                Route::put('/items/{itemId}', [ListaController::class, 'updateCustom'])->name('api.v1.calendars.lista.items.update');
                Route::delete('/items/{itemId}', [ListaController::class, 'destroyCustom'])->name('api.v1.calendars.lista.items.destroy');
                
                // PDF Export
                Route::get('/pdf', [ListaPdfController::class, 'download'])->name('api.v1.calendars.lista.pdf');
                Route::post('/pdf/email', [ListaPdfController::class, 'email'])->name('api.v1.calendars.lista.pdf.email');
                Route::post('/email-html', [ListaPdfController::class, 'emailHtml'])->name('api.v1.calendars.lista.email.html');
            });
        });
        
        // Meal Plans routes
        Route::prefix('plans')->group(function () {
            Route::get('/', [MealPlanController::class, 'index'])->name('api.v1.plans.index');
            Route::get('/{id}', [MealPlanController::class, 'show'])->name('api.v1.plans.show');
            Route::post('/{id}/copy', [MealPlanController::class, 'copy'])->name('api.v1.plans.copy');
            Route::get('/{id}/pdf', [MealPlanPdfController::class, 'download'])->name('api.v1.plans.pdf');
        });
        
        // User Profile routes
        Route::prefix('profile')->group(function () {
            Route::get('/', [ProfileController::class, 'show'])->name('api.v1.profile.show');
            Route::put('/', [ProfileController::class, 'update'])->name('api.v1.profile.update');
            Route::put('/password', [ProfileController::class, 'updatePassword'])->name('api.v1.profile.password');
            Route::post('/photo', [ProfileController::class, 'uploadPhoto'])->name('api.v1.profile.photo');
            Route::delete('/', [ProfileController::class, 'destroy'])->name('api.v1.profile.destroy');
        });
        
        // Filter Bookmarks routes
        Route::prefix('filters/bookmarks')->group(function () {
            Route::get('/', [FilterBookmarkController::class, 'index'])->name('api.v1.filters.bookmarks.index');
            Route::post('/', [FilterBookmarkController::class, 'store'])->name('api.v1.filters.bookmarks.store');
            Route::get('/{id}', [FilterBookmarkController::class, 'show'])->name('api.v1.filters.bookmarks.show');
            Route::put('/{id}', [FilterBookmarkController::class, 'update'])->name('api.v1.filters.bookmarks.update');
            Route::delete('/{id}', [FilterBookmarkController::class, 'destroy'])->name('api.v1.filters.bookmarks.destroy');
            Route::delete('/', [FilterBookmarkController::class, 'destroyMultiple'])->name('api.v1.filters.bookmarks.destroy-multiple');
            Route::post('/load-and-filter', [FilterBookmarkController::class, 'loadAndFilter'])->name('api.v1.filters.bookmarks.load-and-filter');
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
