<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware {
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param \Illuminate\Http\Request $request
     * @return string|null
     */
    protected function redirectTo($request) {
<<<<<<< HEAD
        if (!$request->expectsJson() && !starts_with($request->path(), 'api')) {
=======
        if (!$request->expectsJson() && !str_starts_with($request->path(), 'api')) {
>>>>>>> f884ab14d6e0257bedeca0c123586f0c33072320
            return route('login'); //, ['ref' => \URL::current()]);
        }

        return route('api.api/user/login');
    }
}
