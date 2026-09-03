<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証ユーザーはお気に入り登録ができる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $this->actingAs($user)
            ->post(route('favorites.toggle', $book))
            ->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_未認証ユーザーはお気に入り登録ができない(): void
    {
        $book = Book::factory()->create();

        $this->post(route('favorites.toggle', $book))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('favorites', [
            'book_id' => $book->id,
        ]);
    }

    public function test_認証ユーザーはお気に入り一覧を表示できる(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $user->favoriteBooks()->attach($book->id);

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee($book->title);
    }

    public function test_未認証ユーザーはお気に入り一覧を表示できない(): void
    {
        $this->get(route('favorites.index'))
            ->assertRedirect(route('login'));
    }

    public function test_認証ユーザーはお気に入り解除ができる(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        // テストの事前準備としてお気に入り登録
        $user->favoriteBooks()->attach($book->id);

        $this->actingAs($user)
            ->post(route('favorites.toggle', $book))
            ->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_未認証ユーザーはお気に入り解除ができない(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // テストの事前準備としてお気に入り登録
        $user->favoriteBooks()->attach($book->id);

        $this->post(route('favorites.toggle', $book))
            ->assertRedirect(route('login'));

        // 未認証なのでお気に入りは残っている
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }
}
