<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Applied in all environments
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (app()->isProduction()) {
            // HSTS: tell browsers to only connect over HTTPS for 1 year
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

            // CSP: production assets are all bundled to /build by Vite, so 'self' covers them.
            // Adjust frame-ancestors, img-src, and connect-src if you add CDN/external resources.
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self'",
                "style-src 'self' 'unsafe-inline'",   // unsafe-inline needed for Tailwind class injection
                "img-src 'self' data: blob:",
                "font-src 'self'",
                "connect-src 'self'",
                "object-src 'none'",
                "frame-ancestors 'none'",              // disallow embedding in iframes
                "base-uri 'self'",
                "form-action 'self'",
            ]));
        }

        return $response;
    }
}
