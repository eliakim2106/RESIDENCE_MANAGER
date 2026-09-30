<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restreint une route à certains rôles. Usage : ->middleware('role:admin,super_admin').
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
        $allowedRoles = array_map(fn (string $role): UserRole => UserRole::from($role), $roles);

        abort_unless($request->user()?->hasRole(...$allowedRoles), Response::HTTP_FORBIDDEN);

        return $next($request);
    }
}
