<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\RankingController;
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

// Route::get('/', function () {
//     return view('welcome');
// });

// ログイン必須
Route::middleware('auth')->group(function () {

    Route::resource('books', BookController::class)
        ->except(['index', 'show']); // ゲスト

    Route::resource('genres', GenreController::class);

    Route::resource('favorites', FavoriteController::class)
        ->only(['index', 'store', 'destroy']);

    // お気に入り
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])
        ->name('favorites.toggle');

    // いいね
    Route::post('/reviews/{review}/like', [ReviewController::class, 'toggle'])
        ->name('reviews.like');
    // routes/web.php （または routes/api.php）
    // Route::middleware(['auth'])->group(function () {
    //     Route::post('/reviews/{review}/like', [ReviewController::class, 'toggle'])->name('reviews.like.toggle');
    // });

    Route::resource('reviews', ReviewController::class)
        ->only(['edit', 'update', 'destroy']);
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])
        ->name('reviews.store');

    // マイレポート一覧
    // Route::get('/reports', [BookController::class, 'index']);
});

// ゲストも閲覧可能
Route::resource('books', BookController::class)
    ->only(['index', 'show']);

Route::get('/ranking', [RankingController::class, 'index'])->name('ranking.index');
