<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍一覧を表示できる(): void
    {
        Book::factory()->count(2)->create();

        $this->get(route('books.index'))
            ->assertOk();
    }

    public function test_認証済みユーザーは書籍を登録できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'テスト用の書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ]);

        $book = Book::where('title', 'テスト書籍')->first();

        $this->assertNotNull($book);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    public function test_未認証ユーザーは書籍登録画面にアクセスできない(): void
    {
        $this->get(route('books.create'))
            ->assertRedirect(route('login'));
    }

    public function test_必須項目が未入力の場合は書籍を登録できない(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => '',
            'author' => '',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => 'テスト用の書籍です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [],
        ]);

        $response->assertSessionHasErrors([
            'title',
            'author',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_isb_nが重複する場合は書籍を登録できない(): void
    {
        $user = User::factory()->create();

        Book::factory()->create([
            'isbn' => '9781234567890',
        ]);

        $response = $this->actingAs($user)->post(route('books.store'), [
            'title' => '重複ISBNテスト',
            'author' => 'テスト著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-01-01',
            'description' => null,
            'image_url' => null,
            'genres' => [],
        ]);

        $response->assertSessionHasErrors('isbn');

        $this->assertDatabaseCount('books', 1);
    }

    public function test_書籍詳細を表示できる(): void
    {
        $book = Book::factory()->create([
            'title' => '詳細表示テスト',
        ]);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('詳細表示テスト');
    }

    public function test_書籍の所有者は自分の書籍を更新できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->for($user)->create([
            'title' => '更新前タイトル',
        ]);

        $response = $this->actingAs($user)->put(route('books.update', $book), [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9781234567890',
            'published_date' => '2026-02-01',
            'description' => '更新後の説明です。',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$genre->id],
        ]);

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_書籍の所有者は自分の書籍を削除できる(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->for($user)->create();

        $response = $this->actingAs($user)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }
}
