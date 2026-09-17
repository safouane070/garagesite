<?php

use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publieke routes (geen login nodig)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/aanbod', [CarController::class, 'index'])->name('cars.index');
Route::get('/aanbod/{car}', [CarController::class, 'show'])->name('cars.show');

/*
|--------------------------------------------------------------------------
| Adminroutes (achter login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    // Na het inloggen stuurt Breeze naar route('dashboard'): wij leiden door
    // naar het autobeheer-dashboard.
    Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminCarController::class, 'index'])->name('dashboard');

        Route::resource('cars', AdminCarController::class)->except(['show']);

        // Extra acties buiten de standaard-CRUD.
        Route::patch('cars/{car}/status', [AdminCarController::class, 'updateStatus'])->name('cars.status');
        Route::delete('cars/{car}/images/{image}', [AdminCarController::class, 'destroyImage'])->name('cars.images.destroy');
        Route::patch('cars/{car}/images/{image}/primary', [AdminCarController::class, 'setPrimaryImage'])->name('cars.images.primary');
    });

    // Profielbeheer (van Breeze).
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
