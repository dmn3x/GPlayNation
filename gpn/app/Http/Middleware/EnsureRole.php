<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SRS 3.1.1.2 — route access is scoped to the authenticated user's role.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        abort_unless($request->user() !== null, 403);
        abort_unless(in_array($request->user()->role, $roles, true), 403);

        return $next($request);
    }
}