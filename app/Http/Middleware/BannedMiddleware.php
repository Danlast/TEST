<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class BannedMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->role === \App\Enums\UserRole::BAN) {
            if ($request->route()?->getName() === 'ban' || $request->path() === 'logout') {
                return $next($request);
            }

            return response()->view('pages.ban', [], 403);
        }

        return $next($request);
    }
}
