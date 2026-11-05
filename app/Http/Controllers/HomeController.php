<?php

namespace App\Http\Controllers;

use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Venster voor "verkocht in de afgelopen N dagen" (een periode die we echt bijhouden). */
    public const SOLD_WINDOW_DAYS = 90;

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
        // De drie nieuwste staan in de hero, de rest in de rail eronder. Een prijs-/
        // kilometerdrempel houdt ze in lijn met de premium-toon.
        $newest = Car::available()
            ->whereNotIn('id', $featured->pluck('id'))
            ->where('price', '>=', 15000)
            ->where('mileage', '<=', 200000)
            ->with('primaryImage')
            ->latest()
            ->take(11)
            ->get();
        // De rail houdt ze ook: op een telefoon staat het hero-paneel er niet (zie home).
        $heroNew = $newest->take(3);

        // Bewijs in één regel i.p.v. een rij auto's die je niet kunt kopen.
        $soldRecent = Car::where('status', CarStatus::Sold->value)
            ->where('sold_at', '>=', now()->subDays(self::SOLD_WINDOW_DAYS))
            ->orderByDesc('price')
            ->get(['brand', 'model', 'price']);

        // Voor de zoekbalk in de hero: merk + prijs van de actuele voorraad,
        // zodat de knop live "Toon N auto's" kan zeggen.
        $stockIndex = Car::where('status', '!=', CarStatus::Sold->value)
            ->get(['brand', 'price'])
            ->map(fn (Car $c) => [$c->brand, (int) $c->price]);

        return view('home', compact('featured', 'heroNew', 'newest', 'soldRecent', 'stockIndex'));
    }
}
