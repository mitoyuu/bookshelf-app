<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_お気に入りは追加_解除_再追加できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // ① お気に入り追加
        $this->actingAs($user)
            ->post(route('favorites.toggle', $book))
            ->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // ② お気に入り解除
        $this->post(route('favorites.toggle', $book))
            ->assertRedirect();

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // ③ お気に入り再追加
        $this->post(route('favorites.toggle', $book))
            ->assertRedirect();

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_未認証ユーザーはお気に入りを操作できない(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // お気に入り登録済みの状態にする
        $user->favoriteBooks()->attach($book->id);

        // 未認証ユーザーによる操作
        $this->post(route('favorites.toggle', $book))
            ->assertRedirect(route('login'));

        // お気に入りは変更されていない
        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
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
}
