<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;

class BookController extends Controller
{
    public function index(IndexBookRequest $request)
    {
        $keyword = $request->input('keyword');
        $genreId = $request->input('genre_id');
        $perPage = (int) $request->input('per_page', 20);
        $perPage = min($perPage, 100);

        $query = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        // キーワードが空でない場合のみ検索ロジックを実行（タイトル or 著者）
        if (! empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('author', 'LIKE', "%{$keyword}%");
            });
        }
        // ジャンル絞り込み
        if (! empty($genreId)) {
            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        $books = $query
            ->orderByDesc('created_at')
            ->latest()
            ->paginate($perPage);

        return BookResource::collection($books);
    }
}
