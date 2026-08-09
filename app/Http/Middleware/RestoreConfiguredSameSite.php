<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Sanctum's EnsureFrontendRequestsAreStateful unconditionally forces
 * session.same_site to 'lax' on every stateful API request (see
 * configureSecureCookieSessions() in vendor/laravel/sanctum) — it assumes
 * the SPA and API share a parent domain, where Lax cookies still count as
 * same-site. This app's frontend (Vercel) and backend (Railway) are on
 * entirely different domains, so a Lax cookie is silently dropped by the
 * browser on cross-site fetch/XHR requests: login appears to succeed (the
 * Set-Cookie arrives) but the cookie is never sent back, so every
 * subsequent request looks logged-out.
 *
 * Registered *after* statefulApi() in the "api" group (bootstrap/app.php),
 * so this runs after Sanctum's override but before the response's
 * Set-Cookie header is actually built, restoring whatever SESSION_SAME_SITE
 * is actually configured for this environment (e.g. "none" in production).
 *
 * Reads env() directly rather than config() because Sanctum has already
 * mutated the in-memory config value by the time this runs — this assumes
 * config isn't cached via `php artisan config:cache`, which this app's
 * deploy command does not do.
 */
class RestoreConfiguredSameSite
{
    public function handle(Request $request, Closure $next)
    {
        config(['session.same_site' => env('SESSION_SAME_SITE', 'lax')]);

        return $next($request);
    }
}
