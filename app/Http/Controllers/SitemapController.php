<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        // Alleen zichtbare (te koop staande) auto's horen in de sitemap.
        $cars = Car::query()->available()->latest()->get(['slug', 'updated_at']);

        return response()
            ->view('sitemap', [
                'staticUrls' => [route('home'), route('cars.index')],
                'cars' => $cars,
            ])
            ->header('Content-Type', 'application/xml');
    }
}
