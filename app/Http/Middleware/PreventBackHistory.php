<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBackHistory
{
    /**
     * Add cache-control headers that prevent browsers from storing pages in
     * their back/forward cache (bfcache). This means:
     *
     *  - After logout, pressing Back will not show the cached dashboard.
     *  - After login, pressing Back from the dashboard will not show the
     *    cached login page with stale session state.
     *
     * Applied via the 'no-back' middleware alias to authenticated route
     * groups only (dashboard, student, adviser, admin). Public pages
     * (login, register, archive) are NOT affected.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // withHeaders() only exists on Laravel's Response wrapper.
        // Streaming responses (file downloads) return a raw Symfony StreamedResponse
        // which does not have that method. Use the Symfony-level header API instead.
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}
