<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;
    // お気に入り登録テスト
    // $user = User::factory()->create();
    // $book = Book::factory()->create();

    // $book->favoriteUsers()->attach($user);

    // $this->assertDatabaseHas('favorites', [
    // 'user_id' => $user->id,
    // 'book_id' => $book->id,
    // ]);
}
