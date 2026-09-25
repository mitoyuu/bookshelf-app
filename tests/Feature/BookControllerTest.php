<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍一覧を表示できる(): void
    {
        $books = Book::factory()->count(2)->create();

        $response = $this->get(route('books.index'));

        $response->assertOk();

        foreach ($books as $book) {
            $response->assertSee($book->title);
        }
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
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => '詳細表示テスト',
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'rating' => 5,
            'comment' => 'とても面白い本でした。',
        ]);

        $this->get(route('books.show', $book))
            ->assertOk()
            ->assertSee('詳細表示テスト')
            ->assertSee('とても面白い本でした。');
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

        $review = Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    public function test_キーワードでタイトルまたは著者から書籍を検索できる(): void
    {
        $titleBook = Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        $authorBook = Book::factory()->create([
            'title' => 'PHP基礎',
            'author' => 'Laravel太郎',
        ]);

        $otherBook = Book::factory()->create([
            'title' => 'JavaScript入門',
            'author' => '佐藤花子',
        ]);

        // タイトルで検索
        $this->get(route('books.index', [
            'keyword' => 'Laravel入門',
        ]))
            ->assertOk()
            ->assertSee($titleBook->title)
            ->assertDontSee($otherBook->title);

        // 著者で検索
        $this->get(route('books.index', [
            'keyword' => 'Laravel太郎',
        ]))
            ->assertOk()
            ->assertSee($authorBook->title)
            ->assertDontSee($otherBook->title);
    }

    public function test_ジャンルで書籍を絞り込める(): void
    {
        $targetGenre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $otherGenre = Genre::factory()->create([
            'name' => 'SF',
        ]);

        $targetBook = Book::factory()->create([
            'title' => 'ミステリー小説',
        ]);

        $otherBook = Book::factory()->create([
            'title' => 'SF小説',
        ]);

        $targetBook->genres()->attach($targetGenre);
        $otherBook->genres()->attach($otherGenre);

        $this->get(route('books.index', [
            'genre' => $targetGenre->id,
        ]))
            ->assertOk()
            ->assertSee($targetBook->title)
            ->assertDontSee($otherBook->title);
    }

    public function test_該当する書籍がない場合は表示されない(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        $this->get(route('books.index', [
            'keyword' => '存在しないキーワード',
        ]))
            ->assertOk()
            ->assertDontSee('Laravel入門');
    }

    public function test_ソート指定なしの場合は登録日が新しい順に表示される(): void
    {
        $user = User::factory()->create();

        $oldBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '古い本',
            'created_at' => now()->subDays(2),
        ]);

        $middleBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '中間の本',
            'created_at' => now()->subDay(),
        ]);

        $newBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '新しい本',
            'created_at' => now(),
        ]);

        $response = $this->get(route('books.index'));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertLessThan(
            strpos($content, $middleBook->title),
            strpos($content, $newBook->title)
        );

        $this->assertLessThan(
            strpos($content, $oldBook->title),
            strpos($content, $middleBook->title)
        );
    }

    public function test_newestを指定すると登録日が新しい順に表示される(): void
    {
        $user = User::factory()->create();

        $oldBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '古い本',
            'created_at' => now()->subDays(2),
        ]);

        $middleBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '中間の本',
            'created_at' => now()->subDay(),
        ]);

        $newBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '新しい本',
            'created_at' => now(),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'newest',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertLessThan(
            strpos($content, $middleBook->title),
            strpos($content, $newBook->title)
        );

        $this->assertLessThan(
            strpos($content, $oldBook->title),
            strpos($content, $middleBook->title)
        );
    }

    public function test_oldestを指定すると登録日が古い順に表示される(): void
    {
        $user = User::factory()->create();

        $oldBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '古い本',
            'created_at' => now()->subDays(2),
        ]);

        $middleBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '中間の本',
            'created_at' => now()->subDay(),
        ]);

        $newBook = Book::factory()->create([
            'user_id' => $user->id,
            'title' => '新しい本',
            'created_at' => now(),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'oldest',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertLessThan(
            strpos($content, $middleBook->title),
            strpos($content, $oldBook->title)
        );

        $this->assertLessThan(
            strpos($content, $newBook->title),
            strpos($content, $middleBook->title)
        );
    }

    public function test_titleを指定するとタイトル昇順で表示される(): void
    {
        $user = User::factory()->create();

        $bookC = Book::factory()->create([
            'user_id' => $user->id,
            'title' => 'Cの本',
        ]);

        $bookA = Book::factory()->create([
            'user_id' => $user->id,
            'title' => 'Aの本',
        ]);

        $bookB = Book::factory()->create([
            'user_id' => $user->id,
            'title' => 'Bの本',
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'title',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertLessThan(
            strpos($content, $bookB->title),
            strpos($content, $bookA->title)
        );

        $this->assertLessThan(
            strpos($content, $bookC->title),
            strpos($content, $bookB->title)
        );
    }

    public function test_isb_nから書籍情報を取得できる(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'Laravel入門',
                            'authors' => ['山田太郎'],
                            'publishedDate' => '2026-01-01',
                            'description' => 'Laravelの入門書です。',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/book.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get(route('books.isbn', [
            'isbn' => '9781234567890',
        ]));

        $response->assertOk()
            ->assertJson([
                'title' => 'Laravel入門',
                'author' => '山田太郎',
                'isbn' => '9781234567890',
                'published_date' => '2026-01-01',
                'description' => 'Laravelの入門書です。',
                'image_url' => 'https://example.com/book.jpg',
            ]);
    }

    public function test_isb_nが13桁の数字ではない場合はエラーになる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('books.isbn', [
            'isbn' => '123456',
        ]));

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'ISBNは13桁で入力してください。',
            ]);
    }

    public function test_isb_nに該当する書籍が見つからない場合は404を返す(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [],
            ], 200),
        ]);

        $response = $this->actingAs($user)->get(route('books.isbn', [
            'isbn' => '9781234567890',
        ]));

        $response->assertStatus(404)
            ->assertJson([
                'error' => '該当する書籍が見つかりませんでした。',
            ]);
    }

    public function test_google_books_ap_iとの通信に失敗した場合は502を返す(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'error' => [
                    'code' => 500,
                    'message' => 'Internal Server Error',
                ],
            ], 500),
        ]);

        $response = $this->actingAs($user)->get(route('books.isbn', [
            'isbn' => '9781234567890',
        ]));

        $response->assertStatus(502)
            ->assertJson([
                'error' => 'Google Books APIとの通信に失敗しました。',
            ]);
    }
}
