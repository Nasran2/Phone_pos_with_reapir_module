<?php
namespace App\Http\Responses;
use App\Services\LorryAccessService;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();
        $target = $user && app(LorryAccessService::class)->isLorryUser($user) ? route('distribution.my-lorry.dashboard') : url(config('fortify.home'));
        return redirect()->intended($target);
    }
}
