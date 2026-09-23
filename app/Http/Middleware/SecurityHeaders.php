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

        // Statistiek (optioneel): alleen de host van het ingestelde script erbij.
        $analytics = config('brand.analytics.plausible_domain')
            ? ' ' . preg_replace('#^(https?://[^/]+).*$#', '$1', (string) config('brand.analytics.plausible_script'))
            : '';

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'{$analytics}",
            "style-src 'self' 'unsafe-inline'",
            "font-src 'self'", // fonts zelf gehost (AVG)
            // blob: voor de upload-preview in het beheer (URL.createObjectURL).
            "img-src 'self' data: blob: https:",
            "frame-src https://www.google.com https://iframe.financiallease.nl https://www.financiallease.nl",
            "connect-src 'self'{$analytics}",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "frame-ancestors 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        // Test-/stagingomgeving mag nooit in Google belanden (dubbele content met de echte site).
        if (! app()->isProduction()) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
