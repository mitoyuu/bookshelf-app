<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Tests\TestCase;

class ReadingReportTest extends TestCase
{
    public function test_未ログインの場合はログイン画面へリダイレクトされる(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_ログインユーザー自身のレビューだけが集計される(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $book1 = Book::factory()->create([
            'title' => 'ユーザーAの本',
        ]);

        $book2 = Book::factory()->create([
            'title' => 'ユーザーBの本',
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $book2->id,
            'rating' => 1,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk()
            ->assertViewHas('stats', function ($stats) {
                return $stats['summary']['total_reviews'] === 1
                        && $stats['summary']['books_read'] === 1
                        && $stats['summary']['average_rating'] === 5;
            });
    }

    public function test_同じ書籍に複数レビューしても読了冊数は重複しない(): void
    {
        $user = User::factory()->create();

        $book1 = Book::factory()->create([
            'title' => '同じ本',
        ]);

        $book2 = Book::factory()->create([
            'title' => '別の本',
        ]);

        // 同じ書籍に2件レビュー
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 3,
        ]);

        // 別の書籍に1件レビュー
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 4,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk()
            ->assertViewHas('stats', function ($stats) {
                return $stats['summary']['total_reviews'] === 3
                    && $stats['summary']['books_read'] === 2;
            });
    }

    public function test_評価ごとのレビュー件数を正しく集計できる(): void
    {
        $user = User::factory()->create();

        $ratings = [
            1,
            2,
            2,
            3,
            3,
            3,
            4,
            5,
            5,
        ];

        foreach ($ratings as $rating) {
            $book = Book::factory()->create();

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk()
            ->assertViewHas('stats', function ($stats) {
                return $stats['rating_distribution']->toArray() === [
                    1,
                    2,
                    3,
                    1,
                    2,
                ];
            });
    }

    public function test_レビューの平均評価を正しく計算できる(): void
    {
        $user = User::factory()->create();

        $ratings = [5, 4, 3];

        foreach ($ratings as $rating) {
            $book = Book::factory()->create();

            Review::factory()->create([
                'user_id' => $user->id,
                'book_id' => $book->id,
                'rating' => $rating,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk()
            ->assertViewHas('stats', function ($stats) {
                return $stats['summary']['average_rating'] === 4;
            });
    }

    public function test_平均評価4以上の書籍だけが高評価書籍として集計される(): void
    {
        $user = User::factory()->create();

        $book1 = Book::factory()->create([
            'title' => '高評価の本',
        ]);

        $book2 = Book::factory()->create([
            'title' => '評価4の本',
        ]);

        $book3 = Book::factory()->create([
            'title' => '評価3の本',
        ]);

        // 平均5
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book1->id,
            'rating' => 5,
        ]);

        // 平均4
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book2->id,
            'rating' => 4,
        ]);

        // 平均3 → 高評価書籍には入らない
        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book3->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk()
            ->assertViewHas('stats', function ($stats) {
                $books = $stats['top_rated_books'];

                return $books->count() === 2
                    && $books[0]['title'] === '高評価の本'
                    && $books[1]['title'] === '評価4の本';
            });
    }
}
