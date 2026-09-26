<?php

use App\Http\Controllers\Admin\BackgroundController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\QuoteController;
use App\Http\Controllers\Admin\SavedContextController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->middleware('throttle:6,1')->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    // Quotes (quick-action toggles use dedicated PATCH routes to keep QuoteRequest clean)
    Route::resource('quotes', QuoteController::class)->except('show');
    Route::patch('/quotes/{quote}/verify', [QuoteController::class, 'toggleVerified'])->name('quotes.verify');
    Route::patch('/quotes/{quote}/feature', [QuoteController::class, 'toggleFeature'])->name('quotes.feature');

    Route::resource('backgrounds', BackgroundController::class)->except('show');

    // Saved Contexts (search must come before resource to avoid wildcard collision)
    Route::get('/saved-contexts/search', [SavedContextController::class, 'search'])->name('saved-contexts.search');
    Route::resource('saved-contexts', SavedContextController::class)->except('show');

    // Tags & Categories (management pages not built yet)
    Route::inertia('/tags', 'Admin/Tags/Index')->name('tags.index');
    Route::inertia('/categories', 'Admin/Categories/Index')->name('categories.index');
});

require __DIR__.'/auth.php';
