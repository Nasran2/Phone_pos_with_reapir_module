<?php

namespace App\Http\Middleware;

use App\Services\LorryAccessService;
use Closure;
use Illuminate\Http\Request;

class RequireLorryUser
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user() && app(LorryAccessService::class)->isLorryUser($request->user()), 403);
        app(LorryAccessService::class)->currentAssignment($request->user(), $request->integer('lorry_id') ?: null);
        return $next($request);
    }
}
