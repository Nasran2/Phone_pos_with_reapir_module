<?php

namespace App\Http\Responses;

use App\Services\LorryAccessService;
use App\Support\Features;
use App\Support\SystemSettings;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();
        $target = url(config('fortify.home'));

        if ($user) {
            $lorryService = app(LorryAccessService::class);
            if (! $lorryService->isUnrestricted($user) && $lorryService->isLorryUser($user)) {
                $target = route('distribution.my-lorry.dashboard');
            }
        }

        if (! Features::wholesaleEnabled()) {
            $landing = SystemSettings::get('login_landing_page', 'dashboard');
            $target = url($landing);
            $intended = (string) $request->session()->get('url.intended', '');
            if (str_contains($intended, '/pos/wholesale') || str_contains($intended, '/reports/wholesale-sales') || str_contains($intended, '/my-lorry/order') || str_contains($intended, '/my-lorry/sales') || str_contains($intended, '/distribution/sales')) {
                $request->session()->forget('url.intended');
            }
            if ($user && ! $lorryService->isUnrestricted($user) && $lorryService->isLorryUser($user)) {
                $target = route('distribution.my-lorry.dashboard');
            }
        }

        return redirect()->intended($target);
    }
}
