<?php

namespace App\Http\Controllers;

use App\Enums\CarStatus;
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

        // Recent verkocht — social proof, maar klein: met dezelfde premium-drempel
        // als "Net binnen", en niet meer dan één rij.
        $sold = Car::where('status', CarStatus::Sold->value)
            ->where('price', '>=', 15000)
            ->with('primaryImage')
            ->latest()
            ->take(4)
            ->get();

        // Voor de zoekbalk in de hero: merk + prijs van de actuele voorraad,
        // zodat de knop live "Toon N auto's" kan zeggen.
        $stockIndex = Car::where('status', '!=', CarStatus::Sold->value)
            ->get(['brand', 'price'])
            ->map(fn (Car $c) => [$c->brand, (int) $c->price]);

        return view('home', compact('featured', 'newest', 'sold', 'stockIndex'));
    }
}
