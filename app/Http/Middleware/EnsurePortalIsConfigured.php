<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks the patient portal until the admin has configured GOWA and the
 * Khanza `sik` connection from the Settings page — otherwise visitors would
 * reach a login/dashboard that can never actually work.
 */
class EnsurePortalIsConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Setting::current()->isPortalReady()) {
            return response()->view('patient.setup-pending', status: 503);
        }

        return $next($request);
    }
}
