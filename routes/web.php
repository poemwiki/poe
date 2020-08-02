<?php

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
    return view('welcome');
});

Auth::routes(['verify' => true]);

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


Route::resource('bot', 'BotController');


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
    Route::get('/search',      'PoemController@index')->name('index');
    Route::get('/create',      'PoemController@create')->name('create');
    Route::post('/',           'PoemController@store')->name('store');
    Route::get('/edit/{fakeId}', 'PoemController@edit')->name('edit');
    Route::post('/update/{fakeId}',     'PoemController@update')->name('update');
    Route::get('/{fakeId}',    'PoemController@show')->name('show');
});
