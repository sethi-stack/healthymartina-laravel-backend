<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Redirect framework "dashboard" style routes to Backpack, to avoid confusion.
Route::get('/dashboard', function () {
    return redirect()->to(backpack_url('dashboard'));
})->middleware('auth');

// Test route for Backpack
Route::get('/test-backpack', function () {
    return 'Backpack test route works!';
});

// Test Backpack config
Route::get('/test-backpack-config', function () {
    try {
        return response()->json([
            'backpack_config' => config('backpack.base'),
            'backpack_auth' => class_exists('Backpack\CRUD\BackpackServiceProvider'),
            'backpack_views' => view()->exists('backpack::auth.login'),
            'backpack_views_alt' => view()->exists('backpack.auth.login'),
            'view_paths' => view()->getFinder()->getPaths(),
        ]);
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()]);
    }
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
