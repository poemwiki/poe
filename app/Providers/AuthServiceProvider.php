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
            // TODO only allow poem.poetAuthor.user to change his own poem
            // if($poem->user_id) {return $poem->user_id === $user->id}
            return isset($user->id);
        });
        Gate::define('web.author.change', function ($user) {
            // TODO only allow author.user to change his own author info, or need confirm by author.user
            // if($author->user_id) {return $poem->user_id === $user->id}
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
