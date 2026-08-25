<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        // 1. クエリパラメータ（keyword, genre, sort）を取得
        $keyword = $request->input('keyword');
        $genreId = $request->input('genre');
        $sort = $request->input('sort', 'newest'); // デフォルトは 'latest'と機能要件一覧にはあり

        // 2. クエリビルダの初期化（レビューの平均評価も一緒に取得する）
        $query = Book::with('genres')->withAvg('reviews', 'rating');

        // 3. キーワードが空でない場合のみ検索ロジックを実行（タイトル or 著者）
        if (! empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'LIKE', "%{$keyword}%")
                    ->orWhere('author', 'LIKE', "%{$keyword}%");
            });
        }
        // 4. ジャンル絞り込み
        if (! empty($genreId)) {
            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }
        // 5. ソート（並び替え）の条件分岐
        switch ($sort) {
            case 'oldest':
                // 登録日が古い順
                $query->orderBy('created_at', 'asc');
                break;
            case 'title':
                // タイトル昇順
                $query->orderBy('title', 'asc');
                break;
            case 'rating':
                // 評価が高い順（レビュー平均 'reviews_avg_rating' が高い順）
                // レビューがない（NULL）ものを最後にしつつ、評価が高い順に並べる
                $query->orderByRaw('reviews_avg_rating IS NULL, reviews_avg_rating DESC');
                break;
            case 'latest':
            default:
                // 登録日が新しい順（デフォルト）
                $query->orderBy('created_at', 'desc');
                break;
        }

        // 6. 条件が適用されたクエリに対してページネーションを実行
        $books = $query->paginate(10)->withQueryString();

        // 7. プルダウンに表示するジャンル一覧を取得
        $genres = Genre::orderBy('name')->get();

        return view('books.index', compact('books', 'keyword', 'genres', 'genreId', 'sort'));
    }

    public function create(): View
    {
        $genres = Genre::orderBy('name')->get();

        return view('books.create', compact('genres'));
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $book = $request->user()->books()->create([
            'title' => $validated['title'],
            'author' => $validated['author'],
            // 'isbn' => $validated['isbn'],
            // 'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->sync($validated['genres'] ?? []);

        return redirect()->route('books.show', $book)->with('success', '書籍を登録しました。');
    }

    public function show(Book $book): View
    {
        $book->load(['genres', 'user']);

        return view('books.show', compact('book'));
    }

    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::orderBy('name')->get();

        return view('books.edit', compact('book', 'genres'));
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();

        $this->authorize('update', $book);

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        $book->genres()->sync($validated['genres'] ?? []);

        return redirect()->route('books.show', $book)->with('success', '書籍情報を更新しました。');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index')->with('success', '書籍を削除しました。');
    }
}
