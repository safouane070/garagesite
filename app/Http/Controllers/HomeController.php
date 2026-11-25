<?php

namespace App\Http\Controllers;

use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // Uitgelichte occasions voor de highlight-sectie.
        $featured = Car::available()
            ->featured()
            ->with('primaryImage')
            ->latest()
            ->take(6)
            ->get();

        // Nieuwste binnengekomen auto's — exclusief wat al bij de aanraders staat.
        // Rail "Net binnengereden" onder de aanraders. Een prijs-/
        // kilometerdrempel houdt ze in lijn met de premium-toon.
        $newest = Car::available()
            ->whereNotIn('id', $featured->pluck('id'))
            ->where('price', '>=', 15000)
            ->where('mileage', '<=', 200000)
            ->with('primaryImage')
            ->latest()
            ->take(11)
            ->get();

        // Voor de zoekbalk in de hero: merk + prijs van de actuele voorraad,
        // zodat de knop live "Toon N auto's" kan zeggen.
        $stockIndex = Car::where('status', '!=', CarStatus::Sold->value)
            ->get(['brand', 'price'])
            ->map(fn (Car $c) => [$c->brand, (int) $c->price]);

        return view('home', compact('featured', 'newest', 'stockIndex'));
    }
}
