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
    Route::prefix('v1')->name('api')->group(static function () {
        Route::prefix('campaign')->name('campaign/')->group(static function () {
            Route::get('/', '\App\Http\Controllers\API\CampaignAPIController@index')->name('index');
            // TODO
            Route::get('/show/{id}', '\App\Http\Controllers\API\CampaignAPIController@show')->name('index');
        });
        Route::prefix('poem')->name('poem/')->group(static function () {
            // Route::get('/campaign', '\App\Http\Controllers\API\PoemAPIController@campaignIndex')->name('index');
            Route::get('/random/{num?}/{id?}', '\App\Http\Controllers\API\PoemAPIController@random')->name('random');
            Route::get('/random-nft/{num?}/{id?}', '\App\Http\Controllers\API\PoemAPIController@randomNft')->name('randomNft');

            Route::get('/detail/{id}', '\App\Http\Controllers\API\PoemAPIController@detail')->name('detail');
            Route::get('/nft-detail/{id}', '\App\Http\Controllers\API\PoemAPIController@nftDetail')->name('detail');
            Route::get('/share/{id}', '\App\Http\Controllers\API\PoemAPIController@share')->name('share');
            Route::post('/q', [\App\Http\Controllers\API\PoemAPIController::class, 'query'])->name('query');
        });
        Route::prefix('user')->name('user/')->group(static function () {
            Route::get('/weapp-login', [\App\Http\Controllers\API\LoginWeAppController::class, 'login'])->name('weapp-login');
            Route::get('/timeline/{id}/{page}/{pageSize}', [\App\Http\Controllers\API\UserAPIController::class, 'timeline'])->name('timeline');
        });
        Route::prefix('author')->name('author/')->group(static function () {
            Route::get('/detail/{id}', [\App\Http\Controllers\API\AuthorAPIController::class, 'detail'])->name('detail');
            Route::get('/info/{id}', [\App\Http\Controllers\API\AuthorAPIController::class, 'info'])->name('info');
        });
    });
});

Route::middleware(['auth:api', 'api'])->group(static function () {
    Route::prefix('v1')->name('api')->group(static function () {
        Route::prefix('user')->name('user/')->group(static function () {
            Route::post('/profile', [\App\Http\Controllers\API\UserAPIController::class, 'update'])->name('profile');
            Route::get('/data', [\App\Http\Controllers\API\UserAPIController::class, 'data'])->name('data');
            Route::post('/decrypt', [\App\Http\Controllers\API\LoginWeAppController::class, 'decrypt'])->name('weapp-decrypt');
            Route::post('/avatar', [\App\Http\Controllers\API\UserAPIController::class, 'avatar'])->name('avatar');
            Route::post('/activate-wallet', [\App\Http\Controllers\API\UserAPIController::class, 'activateWallet'])->name('activate-wallet');
            Route::post('/txs', [\App\Http\Controllers\API\UserAPIController::class, 'txs'])->name('txs');
        });
        Route::prefix('poem')->name('poem/')->group(static function () {
            Route::post('/detail/{id}', '\App\Http\Controllers\API\PoemAPIController@detail')->name('detail');
            Route::post('/store', [\App\Http\Controllers\API\PoemAPIController::class, 'store'])->name('store');
            Route::post('/create', [\App\Http\Controllers\API\PoemAPIController::class, 'create'])->name('create');
            Route::get('/mine', [\App\Http\Controllers\API\PoemAPIController::class, 'mine'])->name('mine');
            Route::get('/delete/{poemId}', [\App\Http\Controllers\API\PoemAPIController::class, 'delete'])->name('delete');
            Route::get('/related', [\App\Http\Controllers\API\PoemAPIController::class, 'related'])->name('related');
        });
        Route::prefix('nft')->name('nft/')->group(static function () {
            Route::post('/listing', [\App\Http\Controllers\API\NFTAPIController::class, 'listing'])->name('listing');
            Route::post('/unlisting', [\App\Http\Controllers\API\NFTAPIController::class, 'unlisting'])->name('unlisting');
            Route::post('/buy', [\App\Http\Controllers\API\NFTAPIController::class, 'buy'])->name('buy');
        });
        Route::prefix('review')->name('review/')->group(static function () {
            Route::post('/store', [\App\Http\Controllers\API\ReviewAPIController::class, 'store'])->name('store');
            Route::post('/like/{action}/{id}', [\App\Http\Controllers\API\ReviewAPIController::class, 'like'])->name('like');
            Route::get('/delete/{id}', [\App\Http\Controllers\API\ReviewAPIController::class, 'delete'])->name('store');
            // Route::get('/mine', [\App\Http\Controllers\API\ReviewAPIController::class, 'mine'])->name('mine');
        });
        Route::prefix('score')->name('score/')->group(static function () {
            Route::post('/store', [\App\Http\Controllers\API\ScoreAPIController::class, 'store'])->name('store');
            Route::get('/mine', [\App\Http\Controllers\API\ScoreAPIController::class, 'mine'])->name('mine');
        });
        Route::prefix('notice')->name('notice/')->group(static function () {
            Route::post('/recent', [\App\Http\Controllers\API\NoticeAPIController::class, 'recent'])->name('recent');
        });
        Route::prefix('author')->name('author/')->group(static function () {
            Route::post('/create', [\App\Http\Controllers\API\AuthorAPIController::class, 'create'])->name('create');
            Route::post('/update/{id}', [\App\Http\Controllers\API\AuthorAPIController::class, 'update'])->name('update');
        });
    });
});
