<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('web.poem.change', function ($user) {
            return isset($user->id);
        });
//        Gate::define('web.score.create', function ($user) {
//            return isset($user->id);
//        });
//        Gate::define('web.score.update', function ($user, $score) {
//            return $user->id === $score->user_id;
//        });
//        Gate::define('web.score.delete', function ($user, $score) {
//            return $user->id === $score->user_id;
//        });
    }
}
