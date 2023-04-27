<?php

use App\Models\Poem;
use App\Repositories\PoemRepository;
use App\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome', [
        'poemUrl' => PoemRepository::randomOne()->url
    ]);
});

Auth::routes(['verify' => true]);

Route::get('/logout', 'Auth\LoginController@logout');
Route::get('/home', 'HomeController@index')->middleware('verified');

//Route::get('generator_builder', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@builder')->name('io_generator_builder');
//
//Route::get('field_template', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@fieldTemplate')->name('io_field_template');
//
//Route::get('relation_field_template', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@relationFieldTemplate')->name('io_relation_field_template');
//
//Route::post('generator_builder/generate', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@generate')->name('io_generator_builder_generate');
//
//Route::post('generator_builder/rollback', '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@rollback')->name('io_generator_builder_rollback');
//
//Route::post(
//    'generator_builder/generate-from-file',
//    '\InfyOm\GeneratorBuilder\Controllers\GeneratorBuilderController@generateFromFile'
//)->name('io_generator_builder_generate_from_file');

// Route::resource('contents', 'contentController');
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Auth::routes(['verify' => true]);

Route::get('/home', 'HomeController@index')->middleware('verified');

Route::resource('/bot', 'BotController');

/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function () {
        Route::prefix('poems')->name('poems/')->group(static function () {
            Route::get('/', 'PoemController@index')->name('index');
            Route::get('/create', 'PoemController@create')->name('create');
            Route::post('/', 'PoemController@store')->name('store');
            Route::get('/{poem}/edit', 'PoemController@edit')->name('edit');
            Route::post('/bulk-destroy', 'PoemController@bulkDestroy')->name('bulk-destroy');
            Route::post('/{poem}', 'PoemController@update')->name('update');
            Route::delete('/{poem}', 'PoemController@destroy')->name('destroy');
            Route::post('/{poem}/merge/{mergeToID}', 'PoemController@merge')->name('merge');
        });
    });
});

/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function () {
        Route::prefix('scores')->name('scores/')->group(static function () {
            Route::get('/', 'ScoreController@index')->name('index');
//            Route::get('/create',                                       'ScoreController@create')->name('create');
            Route::post('/', 'ScoreController@store')->name('store');
            Route::get('/{score}/edit', 'ScoreController@edit')->name('edit');
//            Route::post('/bulk-destroy',                                'ScoreController@bulkDestroy')->name('bulk-destroy');
            Route::post('/{score}', 'ScoreController@update')->name('update');
            Route::delete('/{score}', 'ScoreController@destroy')->name('destroy');
        });
    });
});

/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function () {
        Route::prefix('reviews')->name('reviews/')->group(static function () {
            Route::get('/', 'ReviewController@index')->name('index');
            Route::get('/create', 'ReviewController@create')->name('create');
            Route::post('/', 'ReviewController@store')->name('store');
            Route::get('/{review}/edit', 'ReviewController@edit')->name('edit');
            Route::post('/bulk-destroy', 'ReviewController@bulkDestroy')->name('bulk-destroy');
            Route::post('/{review}', 'ReviewController@update')->name('update');
            Route::delete('/{review}', 'ReviewController@destroy')->name('destroy');
        });
    });
});

/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function () {
        Route::prefix('admin-users')->name('admin-users/')->group(static function () {
            Route::get('/', 'AdminUsersController@index')->name('index');
            Route::get('/create', 'AdminUsersController@create')->name('create');
            Route::post('/', 'AdminUsersController@store')->name('store');
            Route::get('/{adminUser}/impersonal-login', 'AdminUsersController@impersonalLogin')->name('impersonal-login');
            Route::get('/{adminUser}/edit', 'AdminUsersController@edit')->name('edit');
            Route::post('/{adminUser}', 'AdminUsersController@update')->name('update');
            Route::delete('/{adminUser}', 'AdminUsersController@destroy')->name('destroy');
            Route::get('/{adminUser}/resend-activation', 'AdminUsersController@resendActivationEmail')->name('resendActivationEmail');
        });
    });
});

/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function () {
        Route::get('/profile', 'ProfileController@editProfile')->name('edit-profile');
        Route::post('/profile', 'ProfileController@updateProfile')->name('update-profile');
        Route::get('/password', 'ProfileController@editPassword')->name('edit-password');
        Route::post('/password', 'ProfileController@updatePassword')->name('update-password');
    });
});

/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function () {
        Route::prefix('genres')->name('genres/')->group(static function () {
            Route::get('/', 'GenreController@index')->name('index');
            Route::get('/create', 'GenreController@create')->name('create');
            Route::post('/', 'GenreController@store')->name('store');
            Route::get('/{genre}/edit', 'GenreController@edit')->name('edit');
            Route::post('/bulk-destroy', 'GenreController@bulkDestroy')->name('bulk-destroy');
            Route::post('/{genre}', 'GenreController@update')->name('update');
            Route::delete('/{genre}', 'GenreController@destroy')->name('destroy');
        });
    });
});

/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function () {
        Route::prefix('dynasties')->name('dynasties/')->group(static function () {
            Route::get('/', 'DynastyController@index')->name('index');
            Route::get('/create', 'DynastyController@create')->name('create');
            Route::post('/', 'DynastyController@store')->name('store');
            Route::get('/{dynasty}/edit', 'DynastyController@edit')->name('edit');
            Route::post('/bulk-destroy', 'DynastyController@bulkDestroy')->name('bulk-destroy');
            Route::post('/{dynasty}', 'DynastyController@update')->name('update');
            Route::delete('/{dynasty}', 'DynastyController@destroy')->name('destroy');
        });
    });
});

/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function () {
        Route::prefix('nations')->name('nations/')->group(static function () {
            Route::get('/', 'NationController@index')->name('index');
            Route::get('/create', 'NationController@create')->name('create');
            Route::post('/', 'NationController@store')->name('store');
            Route::get('/{nation}/edit', 'NationController@edit')->name('edit');
            Route::post('/bulk-destroy', 'NationController@bulkDestroy')->name('bulk-destroy');
            Route::post('/{nation}', 'NationController@update')->name('update');
            Route::delete('/{nation}', 'NationController@destroy')->name('destroy');
        });
    });
});

/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function () {
        Route::prefix('tags')->name('tags/')->group(static function () {
            Route::get('/', 'TagController@index')->name('index');
            Route::get('/create', 'TagController@create')->name('create');
            Route::post('/', 'TagController@store')->name('store');
            Route::get('/{tag}/edit', 'TagController@edit')->name('edit');
            Route::post('/bulk-destroy', 'TagController@bulkDestroy')->name('bulk-destroy');
            Route::post('/{tag}', 'TagController@update')->name('update');
            Route::delete('/{tag}', 'TagController@destroy')->name('destroy');
        });
    });
});

/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function () {
        Route::prefix('categories')->name('categories/')->group(static function () {
            Route::get('/', 'CategoryController@index')->name('index');
            Route::get('/create', 'CategoryController@create')->name('create');
            Route::post('/', 'CategoryController@store')->name('store');
            Route::get('/{category}/edit', 'CategoryController@edit')->name('edit');
            Route::post('/bulk-destroy', 'CategoryController@bulkDestroy')->name('bulk-destroy');
            Route::post('/{category}', 'CategoryController@update')->name('update');
            Route::delete('/{category}', 'CategoryController@destroy')->name('destroy');
        });
    });
});

/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function () {
        Route::prefix('authors')->name('authors/')->group(static function () {
            Route::get('/', 'AuthorController@index')->name('index');
            Route::get('/create', 'AuthorController@create')->name('create');
            Route::post('/', 'AuthorController@store')->name('store');
            Route::get('/{author}/edit', 'AuthorController@edit')->name('edit');
            Route::get('/{author}/verify', 'AuthorController@verify')->name('verify');
            Route::post('/bulk-destroy', 'AuthorController@bulkDestroy')->name('bulk-destroy');
            Route::post('/{author}', 'AuthorController@update')->name('update');
            Route::delete('/{author}', 'AuthorController@destroy')->name('destroy');
        });
    });
});

/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function () {
        Route::prefix('scores')->name('scores/')->group(static function () {
            Route::get('/', 'ScoreController@index')->name('index');
            Route::get('/create', 'ScoreController@create')->name('create');
            Route::post('/', 'ScoreController@store')->name('store');
            Route::get('/{score}/edit', 'ScoreController@edit')->name('edit');
            Route::post('/bulk-destroy', 'ScoreController@bulkDestroy')->name('bulk-destroy');
            Route::post('/{score}', 'ScoreController@update')->name('update');
            Route::delete('/{score}', 'ScoreController@destroy')->name('destroy');
        });
    });
});

/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function () {
        Route::prefix('users')->name('users/')->group(static function () {
            Route::get('/', 'UsersController@index')->name('index');
            Route::get('/create', 'UsersController@create')->name('create');
            Route::post('/', 'UsersController@store')->name('store');
            Route::get('/{user}/edit', 'UsersController@edit')->name('edit');
            Route::get('/{user}/addV', 'UsersController@addV')->name('addV');
            Route::post('/bulk-destroy', 'UsersController@bulkDestroy')->name('bulk-destroy');
            Route::post('/{user}', 'UsersController@update')->name('update');
            Route::delete('/{user}', 'UsersController@destroy')->name('destroy');
        });
    });
});

//Route::resource('poems', 'PoemController');
Route::prefix('poems')->name('poems/')->group(static function () {
    Route::get('/random', 'PoemController@random')->name('random');
    Route::get('/search', 'PoemController@index')->name('index');
    Route::get('/create', 'PoemController@create')->name('create');
    Route::post('/store', 'PoemController@store')->name('store');
    Route::get('/edit/{fakeId}', 'PoemController@edit')->name('edit');
    Route::post('/update/{fakeId}', 'PoemController@update')->name('update');
    Route::get('/contribution/{fakeId}', 'PoemController@showContributions')->name('contribution');
    Route::get('/{fakeId}', 'PoemController@show')->name('show');
});

Route::get('/new', 'PoemController@create')->name('new');

Route::prefix('p')->name('p/')->group(static function () {
    Route::get('/{fakeId}', 'PoemController@show')->name('show');
});

Route::prefix('author')->name('author/')->group(static function () {
    Route::get('/create', 'AuthorController@create')->name('create');
    Route::get('/create-from-wikidata/{wikidata_id}', 'AuthorController@createFromWikidata')->name('create-from-wikidata');
    Route::get('/edit/{fakeId}', 'AuthorController@edit')->name('edit');
    Route::post('/update/{fakeId}', 'AuthorController@update')->name('update');
    Route::post('/store', 'AuthorController@store')->name('store');
    Route::get('/{fakeId}', 'AuthorController@show')->name('show');
    Route::post('/{avatar}', 'AuthorController@avatar')->name('avatar');
});

//Route::get('/login-wechat', function () {
//    $user = session('wechat.oauth_user.default'); // 拿到授权用户资料
//    if(request()->input('code'))
//        dd($user);
//    return $user;
//})->name('login-wechat')->middleware(['web', 'wechat.oauth:default,snsapi_userinfo']);

if (User::isWechat()) {
    Route::any('/login', [\App\Http\Controllers\Auth\LoginWechatController::class, 'login'])
        ->name('login')->middleware(['web', 'wechat.oauth:default,snsapi_userinfo']);
} elseif (User::isWeApp()) {
    Route::any('/login', [\App\Http\Controllers\API\LoginWeAppController::class, 'login'])
        ->name('login');
} else {
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])
        ->name('login');
}

Route::get('/union-login', function () {
    if (User::isWechat() && config('app.env') !== 'local') {
        return redirect(route('login-wechat'));
    }

    return redirect(route('login'));
})->name('union-login');

Route::any('/q', 'QueryController@index')->name('q');
Route::any('/q/nation/{keyword}/{id?}', 'QueryController@nation')->name('queryNation');
Route::any('/q/author/{keyword}/{id?}', 'QueryController@author')->name('queryAuthor');
Route::any('/q/poem/{keyword?}', 'QueryController@poem')->name('queryPoem');
Route::get('/q/{keyword?}', 'QueryController@search')->name('search');
Route::any('/query', 'QueryController@query')->name('query');

Route::any('/calendar', 'CalendarController@index')->name('calendar');
Route::any('/calendar/q/{month}/{day}', 'CalendarController@query')->name('calendar.query');

Route::prefix('campaign')->name('campaign/')->group(static function () {
    Route::get('reward/show/{awardID}', [\App\Http\Controllers\CampaignController::class, 'show'])->name('reward/show');
    Route::get('reward/{campaignId}/{fakeUID}', [\App\Http\Controllers\CampaignController::class, 'reward'])->name('reward');
});

// todo add /contribution to show all contributions
Route::prefix('me')->name('me/')->group(static function () {
    Route::any('/contributions', [\App\Http\Controllers\MeController::class, 'contributions'])->name('contributions');
    Route::any('/', [\App\Http\Controllers\MeController::class, 'index'])->name('me');
});

Route::any('/compare/{ids}', 'PoemController@compare')->name('compare');

Route::get('/page/{page}', function ($page) {
    $view = 'page/' . $page;
    if (view()->exists($view)) {
        return view($view);
    }

    return abort(404);
})->name('page');

Route::get('/poem-card/{id}/{compositionId?}', function ($id, $compositionId = null) {
    $poem = Poem::find($id);
    $pics = $poem->share_pics;
    if ($compositionId && isset($pics[$compositionId])) {
        $path = storage_path($pics[$compositionId]);
    } else {
        abort(404);

        return;
    }

    if (!File::exists($path)) {
        abort(404);

        return;
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = Response::make($file, 200);
    $response->header('Content-Type', $type);

    return $response;
})->name('poem-card');
