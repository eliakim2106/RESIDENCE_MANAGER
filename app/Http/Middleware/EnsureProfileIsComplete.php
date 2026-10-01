<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Un client à qui il manque des informations (compte créé avec Google ou Facebook, par exemple)
 * les complète avant d'accéder à son espace ou de réserver. Il revient ensuite là où il allait.
 */
class EnsureProfileIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->needsProfileCompletion() && ! $request->routeIs('client.profile.complete*')) {
            if ($request->isMethod('GET')) {
                $request->session()->put('url.intended', $request->fullUrl());
            }

            return redirect()->route('client.profile.complete');
        }

        return $next($request);
    }
}
