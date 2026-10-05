<?php

namespace App\Http\Middleware;

use App\Support\Features;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRepairEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Features::repairEnabled(), 404);

        return $next($request);
    }
}
