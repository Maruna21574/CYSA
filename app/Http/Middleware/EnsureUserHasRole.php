<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Coarse, area-level access check (e.g. "role:teacher,school_admin").
 * Record-level access is always decided by Policies.
 */
class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $allowed = array_map(fn (string $role): UserRole => UserRole::from($role), $roles);

        abort_unless($request->user()?->hasRole(...$allowed), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
