<?php

namespace App\Http\Controllers;

use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /** Publieke inhoudspagina's (route-namen) die in de sitemap horen. */
    private const PAGES = [
        'home', 'cars.index', 'diensten', 'financial-lease', 'vw-specialist',
        'over-ons', 'contact', 'voorwaarden', 'privacy',
    ];

    public function index(): Response
    {
        // Alle auto's met een publieke pagina in het aanbod (dus niet verkocht).
        $cars = Car::query()
            ->where('status', '!=', CarStatus::Sold->value)
            ->latest()
            ->get(['slug', 'updated_at']);

        return response()
            ->view('sitemap', [
                'staticUrls' => array_map(fn ($name) => route($name), self::PAGES),
                'cars' => $cars,
            ])
            ->header('Content-Type', 'application/xml');
    }

    /** robots.txt met een absolute sitemap-URL (relatieve paden negeert Google). */
    public function robots(): Response
    {
        if (! app()->isProduction()) {
            return response("User-agent: *\nDisallow: /\n")->header('Content-Type', 'text/plain');
        }

        $body = implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /dashboard',
            '',
            'Sitemap: ' . route('sitemap'),
            '',
        ]);

        return response($body)->header('Content-Type', 'text/plain');
    }
}
