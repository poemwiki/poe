<?php

namespace App\Providers;

use App\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

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

        Gate::define('api.poem.create', function (User $user) {
            return isset($user->id);
        });
        Gate::define('api.poem.update', function (User $user) {
            // TODO 如果声明原创，则只有作者账号或管理员可更改
            return isset($user->id);
        });
        Gate::define('api.review.create', function (User $user) {
            return isset($user->id);
        });

        Gate::define('web.poem.change', function (User $user) {
            // TODO only allow poem.poetAuthor.user to change his own poem
            // if($poem->user_id) {return $poem->user_id === $user->id}
            // TODO 如果声明原创，则只有作者账号或管理员可更改
            return isset($user->id);
        });
        Gate::define('web.author.change', function (User $user) {
            // TODO only allow author.user to change his own author info, or need confirm by author.user
            // if($author->user_id) {return $poem->user_id === $user->id}
            return isset($user->id);
        });

        Passport::routes();

        // token有效期

        Passport::tokensExpireIn(Carbon::now()->addDays(90));

        // 可刷新token时间

        // Passport::refreshTokensExpireIn(Carbon::now()->addDays(2));

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
