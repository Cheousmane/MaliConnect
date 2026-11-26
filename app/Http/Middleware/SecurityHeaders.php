<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Ajoute des en-têtes de sécurité HTTP sûrs pour l'application web.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($request->user() !== null) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
        }

        $response->headers->set('Content-Security-Policy-Report-Only', $this->csp($request));

        return $response;
    }

    private function csp(Request $request): string
    {
        $scriptSrc = "'self' 'unsafe-inline'";
        $styleSrc = "'self' 'unsafe-inline' https://fonts.bunny.net";
        $connectSrc = "'self' https://nominatim.openstreetmap.org https://*.tile.openstreetmap.org https://tile.openstreetmap.org https://*.openstreetmap.org";

        if (app()->isLocal()) {
            $vite = 'http://127.0.0.1:5173 http://localhost:5173 ws://127.0.0.1:5173 ws://localhost:5173 http://*:5173 ws://*:5173';
            $scriptSrc .= ' '.$vite;
            $styleSrc .= ' '.$vite;
            $connectSrc .= ' '.$vite;
        }

        $directives = [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "font-src 'self' https://fonts.bunny.net data:",
            "img-src 'self' data: blob: https: https://*.tile.openstreetmap.org https://tile.openstreetmap.org https://*.openstreetmap.org",
            "connect-src {$connectSrc}",
            "frame-ancestors 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $directives);
    }
}
