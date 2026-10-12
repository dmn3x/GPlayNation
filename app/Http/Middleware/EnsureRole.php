<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        abort_unless($user && $user->role === Role::from($role), 403);

        if ($user->isDeveloper() && ! $user->isVerifiedDeveloper()) {
            abort(403, 'Your developer account is not verified.');
        }

        return $next($request);
    }
}
