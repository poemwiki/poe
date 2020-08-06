<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use App\User;

class CheckInviteCode
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (self::isInviteCodeLimited($request->input('invite_code_from'))) {
            //@TODO redirect to a page that tells invite code limited
            return redirect(RouteServiceProvider::INDEX);
        }

        return $next($request);
    }

    public static function isInviteCodeLimited($code) {
        if(!config('invite_limited')) {
            return false;
        }
        $user = User::select(['id', 'invite_max'])->where('invite_code', $code)->first();
        if(!$user) return true;

        $count = User::where('invited_by', $user->id)->count();
        return $count >= $user->invite_max;
    }
}
