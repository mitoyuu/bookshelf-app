<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * AP01：書籍一覧API
     */
    public function test_書籍一覧を取得できる(): void
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'author',
                    'isbn',
                    'published_date',
                    'description',
                    'image_url',
                    'average_rating',
                    'review_count',
                    'genres',
                ],
            ],
            'meta' => [
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
        ]);
    }

    public function test_書籍一覧はページネーションされる(): void
    {
        Book::factory()->count(21)->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200);
        $response->assertJsonCount(20, 'data');
        $response->assertJsonPath('meta.per_page', 20);
        $response->assertJsonPath('meta.total', 21);
    }

    public function test_書籍一覧にジャンル情報が含まれる(): void
    {
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $book = Book::factory()->create();

        $book->genres()->attach($genre);

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.genres.0.id', $genre->id);
        $response->assertJsonPath('data.0.genres.0.name', '小説');
    }

    public function test_書籍一覧に平均評価とレビュー数が含まれる(): void
    {
        $book = Book::factory()->create();

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user1->id,
            'rating' => 4,
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user2->id,
            'rating' => 5,
        ]);

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200);
        $response->assertJsonPath('data.0.average_rating', '4.5000');
        $response->assertJsonPath('data.0.review_count', 2);
    }

    /**
     * AP02：書籍詳細API
     */
    public function test_指定した書籍の詳細を取得できる(): void
    {
        $book = Book::factory()->create([
            'title' => 'APIテスト用書籍',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'author',
                'isbn',
                'published_date',
                'description',
                'image_url',
                'genres',
                'reviews',
                'average_rating',
                'review_count',
            ],
        ]);
        $response->assertJsonPath('data.id', $book->id);
        $response->assertJsonPath('data.title', 'APIテスト用書籍');
    }

    public function test_書籍詳細にジャンル情報が含まれる(): void
    {
        $genre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $book = Book::factory()->create();

        $book->genres()->attach($genre);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.genres.0.id', $genre->id);
        $response->assertJsonPath('data.genres.0.name', '小説');
    }

    public function test_書籍詳細にレビュー情報が含まれる(): void
    {
        $book = Book::factory()->create();

        $user = User::factory()->create([
            'name' => 'テストユーザー',
        ]);

        Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
            'rating' => 4,
            'comment' => 'とても面白い本でした。',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'reviews' => [
                    '*' => [
                        'id',
                        'user_name',
                        'rating',
                        'comment',
                        'created_at',
                    ],
                ],
            ],
        ]);

        $response->assertJsonPath(
            'data.reviews.0.user_name',
            'テストユーザー'
        );
        $response->assertJsonPath(
            'data.reviews.0.rating',
            4
        );
        $response->assertJsonPath(
            'data.reviews.0.comment',
            'とても面白い本でした。'
        );
    }

    public function test_存在しない_i_dは404の_jso_nを返す(): void
    {
        $response = $this->getJson('/api/v1/books/99999');

        $response->assertStatus(404);
        $response->assertExactJson([
            'error' => '書籍が見つかりませんでした。',
        ]);
    }

    /**
     * AP03：書籍登録API
     */
    public function test_書籍を新規登録できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->postJson('/api/v1/books', [
            'user_id' => $user->id,
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '1234567890123',
            'published_date' => '2026-09-03',
            'description' => 'Laravelの入門書です。',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'author',
                    'isbn',
                    'published_date',
                    'description',
                    'image_url',
                ],
            ])
            ->assertJsonPath('data.title', 'Laravel入門')
            ->assertJsonPath('data.author', '山田太郎');

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'isbn' => '1234567890123',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $response->json('data.id'),
            'genre_id' => $genre->id,
        ]);
    }

    public function test_書籍登録時に必須項目がない場合はバリデーションエラーになる(): void
    {
        $response = $this->postJson('/api/v1/books', [
            'user_id' => User::factory()->create()->id,
            'title' => '',
            'author' => '',
            'genres' => '',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'title',
                'author',
                'genres',
            ])
            ->assertJsonPath('errors.title.0', 'タイトルは必須です。')
            ->assertJsonPath('errors.author.0', '著者名は必須です。')
            ->assertJsonPath('errors.genres.0', 'ジャンルは必須です。');
    }

    public function test_書籍登録時にuser_idがない場合はバリデーションエラーになる(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->postJson('/api/v1/books', [
            'title' => 'Laravel入門',
            'author' => '山田太郎',
            'genres' => [$genre->id],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_id'])
            ->assertJsonPath('errors.user_id.0', '登録者は必須です。');
    }

    public function test_同じ_isb_nの書籍は登録できない(): void
    {
        $user = User::factory()->create();

        Book::factory()->create([
            'isbn' => '1234567890123',
        ]);

        $response = $this->postJson('/api/v1/books', [
            'user_id' => $user->id,
            'title' => '別の書籍',
            'author' => '別の著者',
            'isbn' => '1234567890123',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    /**
     * AP04：書籍更新API
     */
    public function test_指定した_i_dの書籍を更新できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '更新前のタイトル',
            'author' => '更新前の著者',
            'isbn' => '1234567890123',
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'user_id' => $user->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9876543210123',
            'published_date' => '2026-09-04',
            'description' => '更新後の説明です。',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$genre->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'user_id' => $user->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9876543210123',
            'description' => '更新後の説明です。',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_存在しない_i_dの書籍を更新しようとすると404になる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->putJson('/api/v1/books/99999', [
            'user_id' => $user->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'genres' => [$genre->id],
        ]);

        $response
            ->assertStatus(404)
            ->assertJson([
                'error' => '書籍が見つかりませんでした。',
            ]);
    }

    public function test_書籍更新時に必須項目がない場合はバリデーションエラーになる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'user_id' => $user->id,
            'title' => '',
            'author' => '',
            'genres' => '',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'title',
                'author',
                'genres',
            ])
            ->assertJsonPath('errors.title.0', 'タイトルは必須です。')
            ->assertJsonPath('errors.author.0', '著者名は必須です。')
            ->assertJsonPath('errors.genres.0', 'ジャンルは必須です。');
    }

    public function test_他の書籍と同じ_isb_nには更新できない(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '1234567890123',
        ]);

        Book::factory()->create([
            'isbn' => '9876543210123',
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'user_id' => $user->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '9876543210123',
            'genres' => [$genre->id],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['isbn']);
    }

    public function test_更新対象自身と同じ_isb_nでも更新できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'isbn' => '1234567890123',
        ]);

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'user_id' => $user->id,
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '1234567890123',
            'genres' => [$genre->id],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'isbn' => '1234567890123',
            'title' => '更新後のタイトル',
        ]);
    }

    /**
     * AP05：書籍削除API
     */
    public function test_指定した_i_dの書籍と関連データを削除できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $review = Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);

        $user->favoriteBooks()->attach($book->id);

        $book->genres()->attach($genre->id);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_存在しない_i_dの書籍を削除しようとすると404になる(): void
    {
        $response = $this->deleteJson('/api/v1/books/99999');

        $response
            ->assertStatus(404)
            ->assertJson([
                'error' => '書籍が見つかりませんでした。',
            ]);
    }
}
