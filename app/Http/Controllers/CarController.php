<?php

namespace App\Http\Controllers;

use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CarController extends Controller
{
    /** Crawlers en link-previews (WhatsApp, Facebook, …) zijn geen bezoekers. */
    private const BOTS = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|curl|wget|python|headless/i';

    /**
     * Overzichtspagina. We laden de volledige collectie en filteren/sorteren
     * client-side met Alpine — instant, zonder page reload. De initiële
     * merk-/brandstoffilters uit de URL geven we door als voorselectie.
     */
    public function index(Request $request): View
    {
        // Verkochte auto's horen niet in het actuele aanbod (wel in de
        // "met trots verkocht"-showcase op de homepage).
        $cars = Car::query()
            ->where('status', '!=', CarStatus::Sold->value)
            ->with('primaryImage')
            ->orderByDesc('is_featured')
            ->latest()
            ->get();

        // Keuzes en grenzen voor de filterbalk, afgeleid uit de echte data.
        $brands = $cars->pluck('brand')->unique()->sort()->values();
        $fuelTypes = $cars->pluck('fuel_type')->unique()->sort()->values();
        $bodyTypes = $cars->pluck('body_type')->filter()->unique()->sort()->values();

        // Aantallen per keuze (echt uit de data) voor "(26)"-achtige labels.
        $brandCounts = $cars->countBy('brand');
        $fuelCounts = $cars->countBy('fuel_type');
        $bodyCounts = $cars->countBy('body_type');

        $priceMin = (int) floor(($cars->min('price') ?? 0) / 500) * 500;
        $priceMax = (int) ceil(($cars->max('price') ?? 100000) / 500) * 500;
        $yearMin = (int) ($cars->min('year') ?? 2000);
        $yearMax = (int) ($cars->max('year') ?? (int) date('Y'));

        return view('cars.index', [
            'cars' => $cars,
            'brands' => $brands,
            'fuelTypes' => $fuelTypes,
            'bodyTypes' => $bodyTypes,
            'brandCounts' => $brandCounts,
            'fuelCounts' => $fuelCounts,
            'bodyCounts' => $bodyCounts,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
            'yearMin' => $yearMin,
            'yearMax' => $yearMax,
            'initialBrand' => $request->query('brand', ''),
            'initialFuel' => $request->query('fuel_type', ''),
            'initialBody' => $request->query('body_type', ''),
        ]);
    }

    /** Detailpagina met alle specs en de foto-carousel. */
    public function show(Request $request, Car $car): View
    {
        $car->load('images');

        // Weergave tellen: zonder cookies of IP-adres. Bots en de ingelogde
        // beheerder tellen niet mee. Buiten Eloquent om, zodat updated_at (en
        // daarmee de sitemap) niet bij elke weergave verandert.
        if (! $request->user() && ! preg_match(self::BOTS, (string) $request->userAgent())) {
            DB::table('cars')->where('id', $car->id)->increment('views');
        }

        // Vergelijkbare auto's: zelfde merk of carrosserie, exclusief deze.
        $related = Car::available()
            ->with('primaryImage')
            ->where('id', '!=', $car->id)
            ->where(function ($q) use ($car) {
                $q->where('brand', $car->brand)->orWhere('body_type', $car->body_type);
            })
            ->latest()
            ->take(3)
            ->get();

        return view('cars.show', compact('car', 'related'));
    }
}
