<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Onthoudt bij het eerste paginabezoek in een sessie waar de bezoeker vandaan
 * kwam (kanaal + instappagina). Alleen gebruikt als hij een aanvraag doet, zodat
 * het beheer ziet welke kanalen en pagina's aanvragen opleveren. Gebruikt de
 * bestaande sessie: geen extra cookies, niets naar derden.
 */
class RememberLeadSource
{
    public const KEY = 'lead_source';

    /** Verwijzende site → leesbaar kanaal. */
    private const CHANNELS = [
        'google.' => 'Google', 'bing.' => 'Bing', 'duckduckgo.' => 'DuckDuckGo',
        'marktplaats.' => 'Marktplaats', 'autoscout24.' => 'AutoScout24', 'autowereld.' => 'AutoWereld',
        'gaspedaal.' => 'Gaspedaal', 'autotrack.' => 'AutoTrack',
        'facebook.' => 'Facebook', 'instagram.' => 'Instagram', 'tiktok.' => 'TikTok', 'whatsapp.' => 'WhatsApp',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && ! $request->ajax() && ! $request->session()->has(self::KEY)
            && ! $request->is('admin*', 'login', 'dashboard', 'profile', '*.xml', '*.txt')) {
            $request->session()->put(self::KEY, [
                'source' => self::channel($request),
                'landing_page' => Str::limit('/' . ltrim($request->path(), '/'), 250, ''),
            ]);
        }

        return $next($request);
    }

    public static function channel(Request $request): string
    {
        // Eigen campagnelinks (?utm_source=…) gaan voor.
        if ($utm = $request->query('utm_source')) {
            return Str::limit(Str::title((string) $utm), 60, '');
        }

        $host = strtolower((string) parse_url((string) $request->headers->get('referer'), PHP_URL_HOST));
        if ($host === '' || $host === $request->getHost()) {
            return 'Direct';
        }

        foreach (self::CHANNELS as $needle => $name) {
            if (str_contains($host, $needle)) {
                return $name;
            }
        }

        return Str::limit(preg_replace('/^www\./', '', $host), 60, '');
    }
}
