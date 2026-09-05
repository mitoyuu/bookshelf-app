<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 認証不要
    Route::apiResource('books', BookController::class)
        ->only(['index', 'show']);

    // Sanctum認証が必要
    Route::apiResource('books', BookController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('auth:sanctum');
});
// ⇧
// GET     /api/v1/books
// GET     /api/v1/books/{book}
// POST    /api/v1/books
// PUT     /api/v1/books/{book}
// DELETE  /api/v1/books/{book}
// という5つのルートがまとめて作られる

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
