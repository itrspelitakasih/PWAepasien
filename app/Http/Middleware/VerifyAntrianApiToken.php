<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates the Khanza cashier client's calls to POST /api/antrian/panggil
 * (see App\Http\Controllers\Api\AntrianCallController). It's a machine-to-machine
 * call from DlgKasirRalan's "Masuk Poli" action, not a browser session, so a
 * shared bearer token is enough — there's no user to authenticate as.
 */
class VerifyAntrianApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) config('antrian.api_token');

        if ($token === '' || ! hash_equals($token, (string) $request->bearerToken())) {
            abort(401, 'Token tidak valid.');
        }

        return $next($request);
    }
}
