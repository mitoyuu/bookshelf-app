<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\RankingController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// 公開ページ
Route::resource('books', BookController::class)
    ->only(['index', 'show']);

Route::get('/ranking', [RankingController::class, 'index']);

// ログイン必須
Route::middleware('auth')->group(function () {

    Route::resource('books', BookController::class)
        ->except(['index', 'show']);

    Route::resource('genres', GenreController::class);

    Route::resource('favorites', FavoriteController::class)
        ->only(['index', 'store', 'destroy']);

    Route::resource('reviews', ReviewController::class)
        ->only(['store', 'edit', 'update', 'destroy']);
});