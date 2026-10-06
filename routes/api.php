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
