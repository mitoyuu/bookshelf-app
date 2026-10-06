<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * 書籍の評価ランキングを表示する。
     *
     * レビューが存在する書籍を対象に、平均評価とレビュー数を取得し、
     * 平均評価の高い順に上位10件を表示する。
     *
     * @return View 書籍ランキング画面
     */
    public function index(): View
    {
        $rankedBooks = Book::withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->has('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->take(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }
}
