<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware(['api'])->group(static function () {
    Route::prefix('v1')->name('api.')->group(static function () {
        Route::prefix('campaign')->name('campaign/')->group(static function () {
            Route::get('/', '\App\Http\Controllers\API\CampaignAPIController@index')->name('index');
            Route::get('/list/{page?}', [\App\Http\Controllers\API\CampaignAPIController::class, 'list'])->name('list');

            Route::get('/show/{id}', '\App\Http\Controllers\API\CampaignAPIController@show')->name('detail');
        });
        Route::prefix('poem')->name('poem/')->group(static function () {
            // Route::get('/campaign', '\App\Http\Controllers\API\PoemAPIController@campaignIndex')->name('index');
            Route::get('/random/{num?}/{id?}', '\App\Http\Controllers\API\PoemAPIController@random')->name('random');
            Route::get('/random-nft/{num?}/{id?}', '\App\Http\Controllers\API\PoemAPIController@randomNft')->name('randomNft');

            Route::get('/detail/{id}', '\App\Http\Controllers\API\PoemAPIController@detail')->name('detail');
            Route::get('/nft-detail/{id}', '\App\Http\Controllers\API\PoemAPIController@nftDetail')->name('nft-detail');
            Route::get('/share/{id}/{compositionID?}', '\App\Http\Controllers\API\PoemAPIController@share')->name('share');
            Route::post('/q', [\App\Http\Controllers\API\PoemAPIController::class, 'query'])->name('query');
            Route::post('/detect', [\App\Http\Controllers\API\PoemAPIController::class, 'detectLanguage'])->name('detect');
        });
        Route::prefix('user')->name('user/')->group(static function () {
            Route::get('/weapp-login', [\App\Http\Controllers\API\LoginWeAppController::class, 'login'])->name('weapp-login');
            Route::get('/timeline/{id}/{page}/{pageSize}', [\App\Http\Controllers\API\UserAPIController::class, 'timeline'])->name('timeline');
            Route::post('/login', [\App\Http\Controllers\API\LoginController::class, 'login'])->name('login');
        });
        Route::prefix('author')->name('author/')->group(static function () {
            Route::get('/detail/{id}', [\App\Http\Controllers\API\AuthorAPIController::class, 'detail'])->name('detail');
            Route::get('/info/{id}', [\App\Http\Controllers\API\AuthorAPIController::class, 'info'])->name('info');
        });
        Route::get('/contribution', [\App\Http\Controllers\API\ContributionAPIController::class, 'query'])->name('contribution');
    });
});

// TODO don't add poemwiki_session cookie for api response
// To enable web page request pass authenticate, add ',web' after the auth:api,
// then add \Illuminate\Session\Middleware\StartSession::class
// to the $middlewareGroups['api'] in app/Http/Kernel.php.
// But it will add poemwiki_session cookie for api response
Route::middleware(['auth:api,web', 'api'])->group(static function () {
    Route::prefix('v1')->name('api.')->group(static function () {
        Route::prefix('user')->name('user/')->group(static function () {
            Route::post('/profile', [\App\Http\Controllers\API\UserAPIController::class, 'update'])->name('profile');
            Route::get('/data', [\App\Http\Controllers\API\UserAPIController::class, 'data'])->name('data');
            Route::post('/decrypt', [\App\Http\Controllers\API\LoginWeAppController::class, 'decrypt'])->name('weapp-decrypt');
            Route::post('/avatar', [\App\Http\Controllers\API\UserAPIController::class, 'avatar'])->name('avatar');
            Route::post('/activate-wallet', [\App\Http\Controllers\API\UserAPIController::class, 'activateWallet'])->name('activate-wallet');
            Route::post('/txs', [\App\Http\Controllers\API\UserAPIController::class, 'txs'])->name('txs');
        });
        Route::prefix('poem')->name('poem/')->group(static function () {
            // this is not redundant with the above /poem/detail/{id},
            // authenticated user will go this way
            Route::post('/detail/{id}', '\App\Http\Controllers\API\PoemAPIController@detail')->name('detail-authed');
            Route::post('/store', [\App\Http\Controllers\API\PoemAPIController::class, 'store'])->name('store');
            Route::post('/create', [\App\Http\Controllers\API\PoemAPIController::class, 'create'])->name('create');
            Route::get('/mine', [\App\Http\Controllers\API\PoemAPIController::class, 'mine'])->name('mine');
            Route::get('/delete/{poemId}', [\App\Http\Controllers\API\PoemAPIController::class, 'delete'])->name('delete');
            Route::get('/related', [\App\Http\Controllers\API\PoemAPIController::class, 'related'])->name('related');
            Route::any('/import', [\App\Http\Controllers\API\PoemAPIController::class, 'import'])->name('import');
            Route::any('/user/{userID}/{page?}/{pageSize?}', [\App\Http\Controllers\API\PoemAPIController::class, 'userPoems'])->name('user');
        });
        Route::prefix('nft')->name('nft/')->group(static function () {
            Route::post('/listing', [\App\Http\Controllers\API\NFTAPIController::class, 'listing'])->name('listing');
            Route::post('/unlisting', [\App\Http\Controllers\API\NFTAPIController::class, 'unlisting'])->name('unlisting');
            Route::post('/buy', [\App\Http\Controllers\API\NFTAPIController::class, 'buy'])->name('buy');
        });
        Route::prefix('review')->name('review/')->group(static function () {
            Route::post('/store', [\App\Http\Controllers\API\ReviewAPIController::class, 'store'])->name('store');
            Route::post('/like/{action}/{id}', [\App\Http\Controllers\API\ReviewAPIController::class, 'like'])->name('like');
            Route::get('/delete/{id}', [\App\Http\Controllers\API\ReviewAPIController::class, 'delete'])->name('delete');
            // Route::get('/mine', [\App\Http\Controllers\API\ReviewAPIController::class, 'mine'])->name('mine');
        });
        Route::prefix('score')->name('score/')->group(static function () {
            Route::post('/store', [\App\Http\Controllers\API\ScoreAPIController::class, 'store'])->name('store');
            Route::get('/mine', [\App\Http\Controllers\API\ScoreAPIController::class, 'mine'])->name('mine');
        });
        Route::prefix('me')->name('me/')->group(static function () {
            // List authenticated user's five-star poems (score=10) with pagination
            Route::get('/five-star-poems/{page?}', [\App\Http\Controllers\API\MeAPIController::class, 'fiveStarPoems'])->name('five-star-poems');
        });
        Route::prefix('message')->name('message/')->group(static function () {
            Route::get('/recent', [\App\Http\Controllers\API\MessageAPIController::class, 'recent'])->name('recent');
            Route::post('/read/{id}', [\App\Http\Controllers\API\MessageAPIController::class, 'read'])->name('read');
        });
        Route::prefix('author')->name('author/')->group(static function () {
            Route::post('/create', [\App\Http\Controllers\API\AuthorAPIController::class, 'create'])->name('create');
            Route::post('/search', [\App\Http\Controllers\API\AuthorAPIController::class, 'search'])->name('search');
            Route::post('/import', [\App\Http\Controllers\API\AuthorAPIController::class, 'importSimple'])->name('import');
            Route::post('/update/{id}', [\App\Http\Controllers\API\AuthorAPIController::class, 'update'])->name('update');
        });
    });
});
