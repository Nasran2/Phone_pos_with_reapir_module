<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // 1. Authenticated check
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 2. Active status check
        if (!auth()->user()->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors(['email' => 'Your account is deactivated. Please contact support.']);
        }

        // 3. Permission check
        if (!auth()->user()->hasPermission($permission)) {
            abort(403, 'Unauthorized action. You do not have permission: ' . $permission);
        }

        return $next($request);
    }
}
