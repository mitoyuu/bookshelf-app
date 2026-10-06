<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBookRequest;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    /**
     * 書籍一覧を取得する。
     *
     * キーワードやジャンルによる絞り込みに対応し、作成日時の降順で
     * ページネーションした書籍一覧を返す。
     *
     * @param  IndexBookRequest  $request  書籍一覧取得リクエスト
     * @return AnonymousResourceCollection 書籍一覧のAPIリソース
     */
    public function index(IndexBookRequest $request): AnonymousResourceCollection
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

    /**
     * 指定した書籍の詳細を取得する。
     *
     * ジャンルとレビュー投稿者の情報を読み込み、書籍詳細をAPIリソースとして返す。
     *
     * @param  Book  $book  取得対象の書籍
     * @return BookResource 書籍詳細のAPIリソース
     */
    public function show(Book $book): BookResource
    {
        $book->load(['genres', 'reviews.user']);

        return new BookResource($book);
    }

    /**
     * 新しい書籍を作成する。
     *
     * 認証ユーザーを所有者として書籍を作成し、指定されたジャンルを紐付ける。
     *
     * @param  StoreBookRequest  $request  書籍作成リクエスト
     * @return JsonResponse 作成した書籍のAPIレスポンス
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $book = Book::create([
            'user_id' => $request->user()->id,
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

    /**
     * 指定した書籍を更新する。
     *
     * Policyによる認可を行ったうえで書籍情報とジャンルの紐付けを更新する。
     *
     * @param  UpdateBookRequest  $request  書籍更新リクエスト
     * @param  Book  $book  更新対象の書籍
     * @return BookResource 更新した書籍のAPIリソース
     */
    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $book->update([
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

    /**
     * 指定した書籍を削除する。
     *
     * Policyによる認可を行ったうえで書籍を削除する。
     *
     * @param  Book  $book  削除対象の書籍
     * @return JsonResponse 削除結果のAPIレスポンス
     */
    public function destroy(Book $book): JsonResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json(null, 204);
    }
}
