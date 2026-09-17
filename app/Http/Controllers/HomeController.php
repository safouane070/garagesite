<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // Uitgelichte occasions voor de hero/highlight-sectie.
        $featured = Car::available()
            ->featured()
            ->with('primaryImage')
            ->latest()
            ->take(6)
            ->get();

        // Nieuwste binnengekomen auto's — exclusief wat al bij de aanraders
        // staat, zodat dezelfde auto niet twee keer op de homepage verschijnt.
        // Een prijs-/kilometerdrempel houdt de rail in lijn met de premium-toon:
        // geen instappers met extreem hoge kilometerstand naast de toppers.
        $newest = Car::available()
            ->whereNotIn('id', $featured->pluck('id'))
            ->where('price', '>=', 15000)
            ->where('mileage', '<=', 200000)
            ->with('primaryImage')
            ->latest()
            ->take(8)
            ->get();

        // Hero = de duurste beschikbare wagen mét een uitgeknipte cutout. Zo
        // matchen foto én data altijd; verkoopt de topper, dan schuift de hero
        // automatisch door naar de volgende beschikbare wagen met een cutout.
        $hero = Car::available()
            ->orderByDesc('price')
            ->get()
            ->first(fn (Car $car) => $car->cutoutUrl() !== null);

        return view('home', compact('featured', 'newest', 'hero'));
    }
}
