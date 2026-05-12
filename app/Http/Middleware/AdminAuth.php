<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Session-gated admin guard. Lightweight on purpose: there is exactly one
 * operator account, credentials live in config/admin.php (sourced from .env).
 * No DB user table, no Laravel scaffolding, no extra dependencies.
 */
class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->session()->get('admin.authenticated')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('admin.login.show')->with('intended', $request->fullUrl());
        }

        return $next($request);
    }
}
