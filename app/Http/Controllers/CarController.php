<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarController extends Controller
{
    /**
     * Overzichtspagina. We laden de volledige collectie en filteren/sorteren
     * client-side met Alpine — instant, zonder page reload. De initiële
     * merk-/brandstoffilters uit de URL geven we door als voorselectie.
     */
    public function index(Request $request): View
    {
        $cars = Car::query()
            ->with('primaryImage')
            ->orderByDesc('is_featured')
            ->latest()
            ->get();

        // Keuzes en grenzen voor de filterbalk, afgeleid uit de echte data.
        $brands = $cars->pluck('brand')->unique()->sort()->values();
        $fuelTypes = $cars->pluck('fuel_type')->unique()->sort()->values();

        $priceMin = (int) floor(($cars->min('price') ?? 0) / 500) * 500;
        $priceMax = (int) ceil(($cars->max('price') ?? 100000) / 500) * 500;
        $yearMin = (int) ($cars->min('year') ?? 2000);
        $yearMax = (int) ($cars->max('year') ?? (int) date('Y'));

        return view('cars.index', [
            'cars' => $cars,
            'brands' => $brands,
            'fuelTypes' => $fuelTypes,
            'priceMin' => $priceMin,
            'priceMax' => $priceMax,
            'yearMin' => $yearMin,
            'yearMax' => $yearMax,
            'initialBrand' => $request->query('brand', ''),
            'initialFuel' => $request->query('fuel_type', ''),
        ]);
    }

    /** Detailpagina met alle specs en de foto-carousel. */
    public function show(Car $car): View
    {
        $car->load('images');

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
