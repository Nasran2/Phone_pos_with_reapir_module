<?php
namespace App\Http\Responses;
use App\Services\LorryAccessService;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();
        $target = url(config('fortify.home'));

        if ($user) {
            $lorryService = app(LorryAccessService::class);
            if (!$lorryService->isUnrestricted($user) && $lorryService->isLorryUser($user)) {
                $target = route('distribution.my-lorry.dashboard');
            }
        }

        return redirect()->intended($target);
    }
}
