<?php

use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\AuthorController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DynastyController;
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\Admin\NationController;
use App\Http\Controllers\Admin\PoemController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\ScoreController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UsersController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:' . config('admin-auth.defaults.guard'), 'admin'])->name('admin.')->group(static function () {
    Route::get('/profile', [ProfileController::class, 'editProfile'])->name('edit-profile');
    Route::post('/profile', [ProfileController::class, 'updateProfile'])->name('update-profile');
    Route::get('/password', [ProfileController::class, 'editPassword'])->name('edit-password');
    Route::post('/password', [ProfileController::class, 'updatePassword'])->name('update-password');

    Route::prefix('poems')->name('poems/')->group(static function () {
        Route::get('/', [PoemController::class, 'index'])->name('index');
        Route::get('/create', [PoemController::class, 'create'])->name('create');
        Route::post('/', [PoemController::class, 'store'])->name('store');
        Route::get('/{poem}/edit', [PoemController::class, 'edit'])->name('edit');
        Route::post('/bulk-destroy', [PoemController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/{poem}', [PoemController::class, 'update'])->name('update');
        Route::delete('/{poem}', [PoemController::class, 'destroy'])->name('destroy');
        Route::post('/{poem}/merge/{mergeToID}', [PoemController::class, 'merge'])->name('merge');
    });

    Route::prefix('scores')->name('scores/')->group(static function () {
        Route::get('/', [ScoreController::class, 'index'])->name('index');
        Route::post('/', [ScoreController::class, 'store'])->name('store');
        Route::get('/{score}/edit', [ScoreController::class, 'edit'])->name('edit');
        // Route::post('/bulk-destroy', [ScoreController::class, 'bulkDestroy')->]name('bulk-destroy');
        Route::post('/{score}', [ScoreController::class, 'update'])->name('update');
        Route::delete('/{score}', [ScoreController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('reviews')->name('reviews/')->group(static function () {
        Route::get('/', [ReviewController::class, 'index'])->name('index');
        Route::get('/create', [ReviewController::class, 'create'])->name('create');
        Route::post('/', [ReviewController::class, 'store'])->name('store');
        Route::get('/{review}/edit', [ReviewController::class, 'edit'])->name('edit');
        Route::post('/bulk-destroy', [ReviewController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/{review}', [ReviewController::class, 'update'])->name('update');
        Route::delete('/{review}', [ReviewController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('admin-users')->name('admin-users/')->group(static function () {
        Route::get('/', [AdminUsersController::class, 'index'])->name('index');
        Route::get('/create', [AdminUsersController::class, 'create'])->name('create');
        Route::post('/', [AdminUsersController::class, 'store'])->name('store');
        Route::get('/{adminUser}/impersonal-login', [AdminUsersController::class, 'impersonalLogin'])->name('impersonal-login');
        Route::get('/{adminUser}/edit', [AdminUsersController::class, 'edit'])->name('edit');
        Route::post('/{adminUser}', [AdminUsersController::class, 'update'])->name('update');
        Route::delete('/{adminUser}', [AdminUsersController::class, 'destroy'])->name('destroy');
        Route::get('/{adminUser}/resend-activation', [AdminUsersController::class, 'resendActivationEmail'])->name('resendActivationEmail');
    });

    Route::prefix('genres')->name('genres/')->group(static function () {
        Route::get('/', [GenreController::class, 'index'])->name('index');
        Route::get('/create', [GenreController::class, 'create'])->name('create');
        Route::post('/', [GenreController::class, 'store'])->name('store');
        Route::get('/{genre}/edit', [GenreController::class, 'edit'])->name('edit');
        Route::post('/bulk-destroy', [GenreController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/{genre}', [GenreController::class, 'update'])->name('update');
        Route::delete('/{genre}', [GenreController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('dynasties')->name('dynasties/')->group(static function () {
        Route::get('/', [DynastyController::class, 'index'])->name('index');
        Route::get('/create', [DynastyController::class, 'create'])->name('create');
        Route::post('/', [DynastyController::class, 'store'])->name('store');
        Route::get('/{dynasty}/edit', [DynastyController::class, 'edit'])->name('edit');
        Route::post('/bulk-destroy', [DynastyController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/{dynasty}', [DynastyController::class, 'update'])->name('update');
        Route::delete('/{dynasty}', [DynastyController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('nations')->name('nations/')->group(static function () {
        Route::get('/', [NationController::class, 'index'])->name('index');
        Route::get('/create', [NationController::class, 'create'])->name('create');
        Route::post('/', [NationController::class, 'store'])->name('store');
        Route::get('/{nation}/edit', [NationController::class, 'edit'])->name('edit');
        Route::post('/bulk-destroy', [NationController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/{nation}', [NationController::class, 'update'])->name('update');
        Route::delete('/{nation}', [NationController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('tags')->name('tags/')->group(static function () {
        Route::get('/', [TagController::class, 'index'])->name('index');
        Route::get('/create', [TagController::class, 'create'])->name('create');
        Route::post('/', [TagController::class, 'store'])->name('store');
        Route::get('/{tag}/edit', [TagController::class, 'edit'])->name('edit');
        Route::post('/bulk-destroy', [TagController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/{tag}', [TagController::class, 'update'])->name('update');
        Route::delete('/{tag}', [TagController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('categories')->name('categories/')->group(static function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('/create', [CategoryController::class, 'create'])->name('create');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{category}/edit', [CategoryController::class, 'edit'])->name('edit');
        Route::post('/bulk-destroy', [CategoryController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/{category}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{category}', [CategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('authors')->name('authors/')->group(static function () {
        Route::get('/', [AuthorController::class, 'index'])->name('index');
        Route::get('/create', [AuthorController::class, 'create'])->name('create');
        Route::post('/', [AuthorController::class, 'store'])->name('store');
        Route::get('/{author}/edit', [AuthorController::class, 'edit'])->name('edit');
        Route::get('/{author}/verify', [AuthorController::class, 'verify'])->name('verify');
        Route::post('/bulk-destroy', [AuthorController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/{author}', [AuthorController::class, 'update'])->name('update');
        Route::delete('/{author}', [AuthorController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('users')->name('users/')->group(static function () {
        Route::get('/', [UsersController::class, 'index'])->name('index');
        Route::get('/create', [UsersController::class, 'create'])->name('create');
        Route::post('/', [UsersController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UsersController::class, 'edit'])->name('edit');
        Route::get('/{user}/addV', [UsersController::class, 'addV'])->name('addV');
        Route::post('/bulk-destroy', [UsersController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/{user}', [UsersController::class, 'update'])->name('update');
        Route::delete('/{user}', [UsersController::class, 'destroy'])->name('destroy');
    });
});
