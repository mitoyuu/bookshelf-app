<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * 指定した書籍にレビューを投稿する。
     *
     * @param  StoreReviewRequest  $request  レビュー投稿リクエスト
     * @param  Book  $book  レビュー対象の書籍
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function store(StoreReviewRequest $request, Book $book): RedirectResponse
    {
        $validated = $request->validated();

        $review = $request->user()->reviews()->create([
            'book_id' => $book->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return redirect()->route('books.show', $book)->with('success', 'レビューを投稿しました。');
    }

    /**
     * 指定したレビューの編集画面を表示する。
     *
     * @param  Review  $review  編集対象のレビュー
     * @return View レビュー編集画面
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * 指定したレビューを更新する。
     *
     * @param  UpdateReviewRequest  $request  レビュー更新リクエスト
     * @param  Review  $review  更新対象のレビュー
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        $validated = $request->validated();

        $this->authorize('update', $review);

        $review->update([
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return redirect()->route('books.show', $review->book)->with('success', 'レビューを更新しました。');
    }

    /**
     * 指定したレビューを削除する。
     *
     * @param  Review  $review  削除対象のレビュー
     * @return RedirectResponse 書籍詳細画面へのリダイレクト
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        return redirect()->route('books.show', $review->book)->with('success', 'レビューを削除しました。');
    }

    /**
     * 指定したレビューのいいね状態を切り替える。
     *
     * すでにいいねしている場合は解除し、いいねしていない場合は追加する。
     *
     * @param  Review  $review  いいね対象のレビュー
     * @return RedirectResponse 直前のページへのリダイレクト
     */
    public function toggle(Review $review): RedirectResponse
    {
        $user = request()->user();

        // すでにいいねしているか確認
        $alreadyLiked = $user->likedReviews()
            ->where('reviews.id', $review->id)
            ->exists();

        if ($alreadyLiked) {
            // あれば削除（いいね解除）
            $user->likedReviews()->detach($review->id);
        } else {
            // なければ作成（いいね追加）
            $user->likedReviews()->attach($review->id);
        }

        return back();
    }
}
