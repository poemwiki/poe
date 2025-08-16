<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Cache;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class LastOnline {

    public function handle($request, Closure $next) {
        if (!Auth::check()) {
            return $next($request);
        }

        $key = 'online_' . Auth::id();
        $value = (new \DateTime())->format("Y-m-d H:i:s");
        Cache::put($key, $value, now()->addMinutes(7 * 24 * 60));

        return $next($request);
    }
}
