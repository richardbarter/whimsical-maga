<?php

use App\Http\Controllers\Admin\QuoteController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Inertia::render('Public/Home');
})->name('home');

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('dashboard');

    // Quotes
    Route::get('/quotes', [QuoteController::class, 'index'])->name('quotes.index');
    Route::get('/quotes/create', [QuoteController::class, 'create'])->name('quotes.create');
    Route::post('/quotes', [QuoteController::class, 'store'])->name('quotes.store');
    Route::get('/quotes/{quote}/edit', [QuoteController::class, 'edit'])->name('quotes.edit');
    Route::put('/quotes/{quote}', [QuoteController::class, 'update'])->name('quotes.update');
    Route::delete('/quotes/{quote}', [QuoteController::class, 'destroy'])->name('quotes.destroy');
    Route::patch('/quotes/{quote}/verify', [QuoteController::class, 'toggleVerified'])->name('quotes.verify');
    Route::patch('/quotes/{quote}/feature', [QuoteController::class, 'toggleFeature'])->name('quotes.feature');

    // Backgrounds (placeholder routes)
    Route::get('/backgrounds', function () {
        return Inertia::render('Admin/Backgrounds/Index');
    })->name('backgrounds.index');

    Route::get('/backgrounds/create', function () {
        return Inertia::render('Admin/Backgrounds/Index');
    })->name('backgrounds.create');

    // Tags (placeholder routes)
    Route::get('/tags', function () {
        return Inertia::render('Admin/Tags/Index');
    })->name('tags.index');

    Route::get('/tags/create', function () {
        return Inertia::render('Admin/Tags/Index');
    })->name('tags.create');

    // Categories (placeholder routes)
    Route::get('/categories', function () {
        return Inertia::render('Admin/Categories/Index');
    })->name('categories.index');

    Route::get('/categories/create', function () {
        return Inertia::render('Admin/Categories/Index');
    })->name('categories.create');
});

require __DIR__.'/auth.php';
