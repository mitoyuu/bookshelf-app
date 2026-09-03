<?php

namespace Tests\Unit;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍のリレーションが定義されている(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $genre = Genre::factory()->create();
        $book->genres()->attach($genre);

        $review = Review::factory()->create([
            'book_id' => $book->id,
        ]);

        $book->favoriteUsers()->attach($user);

        $this->assertInstanceOf(User::class, $book->user);
        $this->assertTrue($book->genres->contains($genre));
        $this->assertTrue($book->reviews->contains($review));
        $this->assertTrue($book->favoriteUsers->contains($user));
    }

    public function test_公開日は日付型として扱われる(): void
    {
        $book = Book::factory()->create([
            'published_date' => '2026-09-03',
        ]);

        $this->assertInstanceOf(Carbon::class, $book->published_date);
    }
}
