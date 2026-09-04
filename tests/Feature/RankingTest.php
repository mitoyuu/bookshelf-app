<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    public function test_レビュー平均評価の_to_p10書籍が降順で表示できる(): void
    {
        $bookA = Book::factory()->create([
            'title' => '評価5の本',
        ]);

        $bookB = Book::factory()->create([
            'title' => '評価4の本',
        ]);

        $bookC = Book::factory()->create([
            'title' => '評価3の本',
        ]);

        $user = User::factory()->create();

        Review::factory()->create([
            'book_id' => $bookA->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $bookB->id,
            'user_id' => $user->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $bookC->id,
            'user_id' => $user->id,
            'rating' => 3,
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk()
            ->assertSeeInOrder([
                '評価5の本',
                '評価4の本',
                '評価3の本',
            ]);
    }

    public function test_レビューがない書籍はランキングに表示されない(): void
    {
        $bookWithReview = Book::factory()->create([
            'title' => 'レビューありの本',
        ]);

        $bookWithoutReview = Book::factory()->create([
            'title' => 'レビューなしの本',
        ]);

        $user = User::factory()->create();

        Review::factory()->create([
            'book_id' => $bookWithReview->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        $this->get(route('ranking.index'))
            ->assertOk()
            ->assertSee('レビューありの本')
            ->assertDontSee('レビューなしの本');
    }
}
