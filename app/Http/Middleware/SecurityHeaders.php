<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Voegt beveiligingsheaders toe aan elke webrespons. De CSP staat bewust
 * inline scripts/styles én eval toe, omdat Alpine.js die nodig heeft; ze
 * beperkt wél de bronnen en blokkeert clickjacking, base-uri- en object-trucs.
 * Strengere CSP (nonces) zou de Alpine-CSP-build vereisen — bewust niet gedaan.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.trustindex.io",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.trustindex.io",
            "font-src 'self' https://fonts.gstatic.com https://cdn.trustindex.io",
            "img-src 'self' data: https:",
            "frame-src https://www.google.com https://iframe.financiallease.nl https://www.financiallease.nl https://cdn.trustindex.io",
            "connect-src 'self' https://cdn.trustindex.io",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
