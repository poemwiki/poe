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
        });
        Route::prefix('poem')->name('poem/')->group(static function() {
            Route::get('/', '\App\Http\Controllers\API\PoemAPIController@index')->name('index');
            Route::get('/detail/{id}', '\App\Http\Controllers\API\PoemAPIController@detail')->name('detail');
        });
        Route::prefix('user')->name('user/')->group(static function() {
            Route::get('/weapp-login', [\App\Http\Controllers\API\LoginWeAppController::class, 'login'])->name('weapp-login');
        });
    });
});

Route::middleware(['auth:api', 'api'])->group(static function () {
    Route::prefix('v1')->name('api')->group(static function() {
        Route::prefix('user')->name('user/')->group(static function() {
            Route::post('/profile', [\App\Http\Controllers\API\UserAPIController::class, 'update'])->name('profile');
            Route::post('/decrypt', [\App\Http\Controllers\API\LoginWeAppController::class, 'decrypt'])->name('weapp-decrypt');
        });
        // Route::prefix('score')->name('score/')->group(static function() {
        //     Route::get('/', '\App\Http\Controllers\API\ScoreAPIController@index')->name('index');
        // });
        // Route::prefix('score')->name('score/')->group(static function() {
        //     Route::get('/', '\App\Http\Controllers\API\ReviewAPIController@index')->name('index');
        // });
    });
});