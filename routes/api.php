<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::apiResource('books', BookController::class);
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
