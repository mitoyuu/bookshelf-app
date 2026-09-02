<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
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

    public function show(Book $book)
    {
        $book->load(['genres', 'reviews.user']);

        return new BookResource($book);
    }

    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();

        $book = Book::create([
            'user_id' => $validated['user_id'],
            // AP06：Sanctum認証追加後、下記へ変更する
            // $request->user()->id,
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'] ?? null,
            'published_date' => $validated['published_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->sync($validated['genres'] ?? []);
        $book->load('genres');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);

    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $validated = $request->validated();

        $book->update([
            'user_id' => $validated['user_id'],
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'] ?? null,
            'published_date' => $validated['published_date'] ?? null,
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->sync($validated['genres'] ?? []);
        $book->load('genres');

        return new BookResource($book);
    }

    // public function destroy(Book $book)
    // {
    //     $book->delete();

    //     return response()->json(null, 204);
    // }
}
