<?php

use Illuminate\Http\Request;
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
    Route::prefix('v1')->name('api')->group(static function() {
        Route::prefix('campaign')->name('campaign/')->group(static function() {
            Route::get('/', '\App\Http\Controllers\API\CampaignAPIController@index')->name('index');
            // TODO
            Route::get('/show/{id}', '\App\Http\Controllers\API\CampaignAPIController@show')->name('index');
        });
        Route::prefix('poem')->name('poem/')->group(static function() {
            // Route::get('/campaign', '\App\Http\Controllers\API\PoemAPIController@campaignIndex')->name('index');
            Route::get('/random/{num?}/{id?}', '\App\Http\Controllers\API\PoemAPIController@random')->name('random');

            Route::get('/detail/{id}', '\App\Http\Controllers\API\PoemAPIController@detail')->name('detail');
            Route::get('/share/{id}', '\App\Http\Controllers\API\PoemAPIController@share')->name('share');
        });
        Route::prefix('user')->name('user/')->group(static function() {
            Route::get('/weapp-login', [\App\Http\Controllers\API\LoginWeAppController::class, 'login'])->name('weapp-login');
        });
        Route::prefix('author')->name('author/')->group(static function() {
            Route::get('/detail/{id}', [\App\Http\Controllers\API\AuthorAPIController::class, 'detail'])->name('detail');
        });
    });
});

Route::middleware(['auth:api', 'api'])->group(static function () {
    Route::prefix('v1')->name('api')->group(static function() {
        Route::prefix('user')->name('user/')->group(static function() {
            Route::post('/profile', [\App\Http\Controllers\API\UserAPIController::class, 'update'])->name('profile');
            Route::get('/data', [\App\Http\Controllers\API\UserAPIController::class, 'data'])->name('data');
            Route::post('/decrypt', [\App\Http\Controllers\API\LoginWeAppController::class, 'decrypt'])->name('weapp-decrypt');
        });
        Route::prefix('poem')->name('poem/')->group(static function() {
            Route::post('/detail/{id}', '\App\Http\Controllers\API\PoemAPIController@detail')->name('detail');
            Route::post('/store', [\App\Http\Controllers\API\PoemAPIController::class, 'store'])->name('store');
            Route::get('/mine', [\App\Http\Controllers\API\PoemAPIController::class, 'mine'])->name('mine');
            Route::get('/delete/{poemId}', [\App\Http\Controllers\API\PoemAPIController::class, 'delete'])->name('delete');
            Route::get('/related', [\App\Http\Controllers\API\PoemAPIController::class, 'related'])->name('related');
        });
        Route::prefix('review')->name('review/')->group(static function() {
            Route::post('/store', [\App\Http\Controllers\API\ReviewAPIController::class, 'store'])->name('store');
            Route::post('/like/{action}/{id}', [\App\Http\Controllers\API\ReviewAPIController::class, 'like'])->name('like');
            // Route::get('/mine', [\App\Http\Controllers\API\ReviewAPIController::class, 'mine'])->name('mine');
        });
        Route::prefix('score')->name('score/')->group(static function() {
            Route::post('/store', [\App\Http\Controllers\API\ScoreAPIController::class, 'store'])->name('store');
            Route::get('/mine', [\App\Http\Controllers\API\ScoreAPIController::class, 'mine'])->name('mine');
        });
    });
});