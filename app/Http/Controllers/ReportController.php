<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * マイ読書レポートを表示する
     */
    public function index(): View
    {
        // ログイン中のユーザーが投稿したレビューを取得する
        $user = auth()->user();

        $reviews = $user->reviews()
            ->with('book.genres')
            ->get();

        // 書籍ごとにレビューをまとめる
        $topRatedBooks = $reviews
            ->groupBy('book_id')
            ->map(function ($bookReviews) {
                $review = $bookReviews->first();

                return [
                    'id' => $review->book->id,
                    'title' => $review->book->title,
                    'author' => $review->book->author,
                    'average_rating' => $bookReviews->avg('rating'),
                    'rating' => round($bookReviews->avg('rating')),
                ];
            })
            // 平均評価4以上の書籍だけ残す
            ->filter(function ($book) {
                return $book['average_rating'] >= 4;
            })
            // 平均評価の高い順に並べる
            ->sortByDesc('average_rating')
            // 上位5冊
            ->take(5)
            ->values();

        // 評価ごとのレビュー件数を集計する
        $ratingCounts = $reviews
            ->groupBy('rating')
            ->map->count();

        // 1〜5の評価をすべて揃える
        $ratingDistribution = collect([1, 2, 3, 4, 5])
            ->map(fn ($rating) => $ratingCounts->get($rating, 0));

        // レビューをジャンルごとのデータに展開する
        $genreReviews = $reviews->flatMap(function ($review) {
            return $review->book->genres->map(function ($genre) use ($review) {
                return [
                    'genre_id' => $genre->id,
                    'genre_name' => $genre->name,
                    'rating' => $review->rating,
                ];
            });
        });

        // ジャンルごとのレビュー件数・平均評価を集計する
        $genreRatings = $genreReviews
            ->groupBy('genre_id')
            ->map(function ($genreReviews) {
                $first = $genreReviews->first();

                return [
                    'id' => $first['genre_id'],
                    'name' => $first['genre_name'],
                    'count' => $genreReviews->count(),
                    'average_rating' => $genreReviews->avg('rating'),
                ];
            })
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();

        // レポートに表示するデータをまとめる
        $stats = [
            'summary' => [
                'total_reviews' => $reviews->count(),
                'books_read' => $reviews->pluck('book_id')->unique()->count(),
                'average_rating' => $reviews->avg('rating') ?? 0,
            ],

            'rating_distribution' => $ratingDistribution,

            'top_rated_books' => $topRatedBooks,

            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', [
            'stats' => $stats,
        ]);
    }
}
