<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || Auth::user()->role !== \App\Enums\UserRole::ADMIN) {
            abort(403, 'Доступ запрещён. Только для администраторов.');
        }

        return $next($request);
    }
}