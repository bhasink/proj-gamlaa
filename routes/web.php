<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\InspirationController as AdminInspirationController;
use App\Http\Controllers\DesignInspirationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('home');
Route::get('/', function () {
    return redirect()->route('design-inspiration.index');
});

Route::get('/design-inspiration', [DesignInspirationController::class, 'index'])
    ->name('design-inspiration.index');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login',  [AdminAuthController::class, 'showLogin'])->name('login.show');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1')->name('login');
    Route::post('/logout',[AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware(['admin', 'admin.csp'])->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/inspirations',                          [AdminInspirationController::class, 'index'])->name('inspirations.index');
        Route::get('/inspirations/new',                      [AdminInspirationController::class, 'create'])->name('inspirations.create');
        Route::post('/inspirations',                         [AdminInspirationController::class, 'store'])->name('inspirations.store');
        Route::get('/inspirations/{inspiration}/edit',       [AdminInspirationController::class, 'edit'])->name('inspirations.edit');
        Route::put('/inspirations/{inspiration}',            [AdminInspirationController::class, 'update'])->name('inspirations.update');
        Route::delete('/inspirations/{inspiration}',         [AdminInspirationController::class, 'destroy'])->name('inspirations.destroy');
        Route::post('/inspirations/bulk',                    [AdminInspirationController::class, 'bulk'])->name('inspirations.bulk');
        Route::post('/inspirations/reorder',                 [AdminInspirationController::class, 'reorder'])->name('inspirations.reorder');
        Route::post('/inspirations/{inspiration}/toggle',    [AdminInspirationController::class, 'togglePublish'])->name('inspirations.toggle');

        Route::get('/categories',                            [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/new',                        [AdminCategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories',                           [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit',            [AdminCategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}',                 [AdminCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}',              [AdminCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/categories/reorder',                   [AdminCategoryController::class, 'reorder'])->name('categories.reorder');
        Route::post('/categories/{category}/toggle',         [AdminCategoryController::class, 'toggleActive'])->name('categories.toggle');
    });
});
