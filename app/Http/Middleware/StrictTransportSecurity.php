<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tell browsers to always use HTTPS for this site (HSTS) – only on HTTPS responses,
 * so local development over http://127.0.0.1 is not affected.
 */
class StrictTransportSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
