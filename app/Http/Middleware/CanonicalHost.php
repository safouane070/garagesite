<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Productie: één adres voor de hele site. www.autobedrijfrijswijk.nl (via de
 * CNAME op dezelfde server) gaat permanent naar het adres uit APP_URL, zodat
 * Google geen dubbele pagina's ziet en links/cookies op één domein blijven.
 */
class CanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $canonical = parse_url(config('app.url'), PHP_URL_HOST);

        if (app()->isProduction() && $canonical && $request->getHost() !== $canonical) {
            return redirect()->to(rtrim(config('app.url'), '/') . $request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
