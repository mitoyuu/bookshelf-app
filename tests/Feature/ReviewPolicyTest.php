<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_更新と削除はレビュー投稿者だけが許可される(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
        ]);

        $this->assertTrue($owner->can('update', $review));
        $this->assertTrue($owner->can('delete', $review));
        $this->assertFalse($other->can('update', $review));
        $this->assertFalse($other->can('delete', $review));
    }
}
