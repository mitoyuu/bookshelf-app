<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーはレビューを投稿できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
            route('reviews.store', $book),
            [
                'rating' => 5,
                'comment' => 'とても面白い本でした。',
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);
    }

    public function test_未認証ユーザーにはレビュー投稿用のログイン案内が表示される(): void
    {
        $book = Book::factory()->create();

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('レビューを投稿するには')
            ->assertSee('ログイン');
    }

    public function test_評価とコメントが未入力の場合はレビューを投稿できない(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)->post(
            route('reviews.store', $book),
            [
                'rating' => '',
                'comment' => '',
            ]
        );

        $response->assertSessionHasErrors([
            'rating',
            'comment',
        ]);

        $this->assertDatabaseMissing('reviews', [
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_レビューの投稿者は自分のレビューを編集できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 3,
            'comment' => '普通でした。',
        ]);

        $response = $this->actingAs($user)->put(
            route('reviews.update', $review),
            [
                'rating' => 5,
                'comment' => 'とても面白かったです。',
            ]
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白かったです。',
        ]);
    }

    public function test_レビューの投稿者は自分のレビューを削除できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->delete(
            route('reviews.destroy', $review)
        );

        $response->assertRedirect();

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_レビューにいいねを追加できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)->post(
            route('reviews.like', $review)
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }

    public function test_レビューのいいねを解除できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $review->likedByUsers()->attach($user->id);

        $response = $this->actingAs($user)->post(
            route('reviews.like', $review)
        );

        $response->assertRedirect();

        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);
    }
}
