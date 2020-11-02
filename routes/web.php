<?php

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


Route::resource('contents', 'contentController');
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');


Auth::routes(['verify' => true]);

Route::get('/home', 'HomeController@index')->middleware('verified');


Route::resource('/bot', 'BotController');


/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function() {
        Route::prefix('poems')->name('poems/')->group(static function() {
            Route::get('/',                                             'PoemController@index')->name('index');
            Route::get('/create',                                       'PoemController@create')->name('create');
            Route::post('/',                                            'PoemController@store')->name('store');
            Route::get('/{poem}/edit',                                  'PoemController@edit')->name('edit');
            Route::post('/bulk-destroy',                                'PoemController@bulkDestroy')->name('bulk-destroy');
            Route::post('/{poem}',                                      'PoemController@update')->name('update');
            Route::delete('/{poem}',                                    'PoemController@destroy')->name('destroy');
        });
    });
});

//Route::resource('poems', 'PoemController');
Route::prefix('poems')->name('poems/')->group(static function() {
    Route::get('/random',      'PoemController@random')->name('random');
    Route::get('/search',      'PoemController@index')->name('index');
    Route::get('/create',      'PoemController@create')->name('create');
    Route::post('/store',           'PoemController@store')->name('store');
    Route::get('/edit/{fakeId}', 'PoemController@edit')->name('edit');
    Route::post('/update/{fakeId}',     'PoemController@update')->name('update');
    Route::get('/{fakeId}',    'PoemController@show')->name('show');
    Route::get('/contribution/{fakeId}',    'PoemController@showContributions')->name('contribution');
});

Route::prefix('p')->name('p/')->group(static function() {
    Route::get('/{fakeId}',    'PoemController@show')->name('show');
});

Route::prefix('poet')->name('poet/')->group(static function() {
    Route::get('/{poetName}',    'PoetController@show')->name('show');
});


/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function() {
        Route::prefix('scores')->name('scores/')->group(static function() {
            Route::get('/',                                             'ScoreController@index')->name('index');
//            Route::get('/create',                                       'ScoreController@create')->name('create');
            Route::post('/',                                            'ScoreController@store')->name('store');
            Route::get('/{score}/edit',                                 'ScoreController@edit')->name('edit');
//            Route::post('/bulk-destroy',                                'ScoreController@bulkDestroy')->name('bulk-destroy');
            Route::post('/{score}',                                     'ScoreController@update')->name('update');
            Route::delete('/{score}',                                   'ScoreController@destroy')->name('destroy');
        });
    });
});

/* Auto-generated admin routes */
Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->group(static function () {
    Route::prefix('admin')->namespace('Admin')->name('admin/')->group(static function() {
        Route::prefix('reviews')->name('reviews/')->group(static function() {
            Route::get('/',                                             'ReviewController@index')->name('index');
            Route::get('/create',                                       'ReviewController@create')->name('create');
            Route::post('/',                                            'ReviewController@store')->name('store');
            Route::get('/{review}/edit',                                'ReviewController@edit')->name('edit');
            Route::post('/bulk-destroy',                                'ReviewController@bulkDestroy')->name('bulk-destroy');
            Route::post('/{review}',                                    'ReviewController@update')->name('update');
            Route::delete('/{review}',                                  'ReviewController@destroy')->name('destroy');
        });
    });
});


//Route::get('/login-wechat', function () {
//    $user = session('wechat.oauth_user.default'); // 拿到授权用户资料
//    if(request()->input('code'))
//        dd($user);
//    return $user;
//})->name('login-wechat')->middleware(['web', 'wechat.oauth:default,snsapi_userinfo']);

if(User::isWechat()) {
    Route::any('/login', [\App\Http\Controllers\Auth\LoginWechatController::class, 'login'])
        ->name('login')->middleware(['web', 'wechat.oauth:default,snsapi_userinfo']);
} else {
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])
        ->name('login');
}

Route::get('/union-login', function () {
    if(User::isWechat()) {
        return redirect(route('login-wechat'));
    }
    return redirect(route('login'));
})->name('union-login');

Route::get('/{id}', 'PoemController@showPoem')->name('poem');
