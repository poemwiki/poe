<?php

use App\Http\Controllers;
use App\Models\Poem;
use App\Repositories\PoemRepository;
use App\User;
use Illuminate\Support\Facades\Auth;
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

Route::get('/bot', [Controllers\BotController::class, 'index']);

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

// Route::get('/login-wechat', function () {
//    $user = session('wechat.oauth_user.default'); // 拿到授权用户资料
//    if(request()->input('code'))
//        dd($user);
//    return $user;
// })->name('login-wechat')->middleware(['web', 'wechat.oauth:default,snsapi_userinfo']);

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
    Route::get('/', [\App\Http\Controllers\CampaignController::class, 'index'])->name('index');
    Route::get('/{campaignID}/poems', [\App\Http\Controllers\CampaignController::class, 'poems'])->name('poems');
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
