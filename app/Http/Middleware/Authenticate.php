<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware {
    public function handle($request, Closure $next, ...$guards) {
        try {
            return parent::handle($request, $next, ...$guards);
        } catch (AuthenticationException $exception) {
            if ($request->expectsJson() || !$request->routeIs('poems/contribution', 'compare')) {
                throw $exception;
            }

            // The admin-auth exception handler discards custom login targets.
            // WeChat login needs ref to return to the requested page.
            $ref = $request->getPathInfo();
            if ($query = $request->getQueryString()) {
                $ref .= '?' . $query;
            }

            return redirect()->guest(route('login', ['ref' => $ref]));
        }
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function redirectTo($request) {
        if (str_starts_with($request->path(), 'api')) {
            $request->headers->set('Accept', 'application/json');

            return route('api.user/login');
        }

        return route('login');
    }
}
