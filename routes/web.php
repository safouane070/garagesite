<?php

use App\Http\Controllers\Admin\CarController as AdminCarController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Models\Car;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publieke routes (geen login nodig)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/aanbod', [CarController::class, 'index'])->name('cars.index');
Route::get('/aanbod/{car}', [CarController::class, 'show'])->name('cars.show');

// Contact-/interesseaanvraag vanaf de site. Throttle houdt bots/spam af.
Route::post('/aanvraag', [LeadController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('leads.store');

// Adressen van de oude WordPress-site (staan in Google en in gedeelde links):
// permanent doorsturen zodat bezoekers en zoekposities niet op een 404 stranden.
Route::permanentRedirect('/occasions', '/aanbod');
Route::permanentRedirect('/privacy-policy', '/privacybeleid');
Route::get('/voertuig/{slug}', function (string $slug) {
    $car = Car::firstWhere('dealer_slug', $slug);

    return $car ? redirect()->route('cars.show', $car, 301) : redirect()->route('cars.index', status: 301);
});

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

// Statische inhoudspagina's (lezen zelf uit config/brand.php + shared components).
Route::view('/diensten', 'pages.diensten')->name('diensten');
Route::view('/financial-lease', 'pages.financial-lease')->name('financial-lease');
Route::view('/volkswagen-specialist', 'pages.vw-specialist')->name('vw-specialist');
Route::view('/over-ons', 'pages.over-ons')->name('over-ons');
Route::view('/contact', 'pages.contact')->name('contact');
Route::view('/algemene-voorwaarden', 'pages.voorwaarden')->name('voorwaarden');
Route::view('/privacybeleid', 'pages.privacy')->name('privacy');

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
        Route::patch('cars/{car}/images/{image}/move', [AdminCarController::class, 'moveImage'])->name('cars.images.move');

        // Aanvragen-inbox (leads van de site).
        Route::get('aanvragen', [AdminLeadController::class, 'index'])->name('leads.index');
        Route::patch('aanvragen/{lead}/afgehandeld', [AdminLeadController::class, 'toggle'])->name('leads.toggle');
        Route::delete('aanvragen/{lead}', [AdminLeadController::class, 'destroy'])->name('leads.destroy');

        Route::view('hulp', 'admin.help')->name('help');
    });

    // Profielbeheer (van Breeze).
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
