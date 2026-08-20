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

    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

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

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        return redirect()->route('books.show', $review->book)->with('success', 'レビューを削除しました。');
    }

    /**
     * レビューのいいねをトグルする
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
