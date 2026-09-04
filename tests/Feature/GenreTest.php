<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証ユーザーはジャンルを登録できる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => 'ミステリー',
            ])
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'name' => 'ミステリー',
        ]);
    }

    public function test_未認証ユーザーはジャンルを登録できない(): void
    {
        $this->post(route('genres.store'), [
            'name' => 'ミステリー',
        ])
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('genres', [
            'name' => 'ミステリー',
        ]);
    }

    public function test_認証ユーザーはジャンルを編集できる(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => '推理小説',
            ])
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '推理小説',
        ]);
    }

    public function test_ジャンル編集時は自身のジャンル名を除外して一意性をチェックする(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => 'ミステリー',
            ])
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'ミステリー',
        ]);
    }

    public function test_ジャンル編集時に他のジャンルと名前が重複する場合は更新できない(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $genre = Genre::factory()->create([
            'name' => 'SF',
        ]);

        $this->actingAs($user)
            ->put(route('genres.update', $genre), [
                'name' => 'ミステリー',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'SF',
        ]);
    }

    public function test_未認証ユーザーはジャンルを編集できない(): void
    {
        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->put(route('genres.update', $genre), [
            'name' => '推理小説',
        ])
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'ミステリー',
        ]);
    }

    public function test_認証ユーザーはジャンルを削除できる(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->actingAs($user)
            ->delete(route('genres.destroy', $genre))
            ->assertRedirect(route('genres.index'));

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_書籍の紐付きがある場合はジャンルを削除できない(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $book = Book::factory()->for($user)->create();

        $book->genres()->attach($genre->id);

        $this->actingAs($user)
            ->delete(route('genres.destroy', $genre))
            ->assertRedirect();

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => 'ミステリー',
        ]);
    }

    public function test_ジャンル名が未入力の場合はジャンルを登録できない(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => '',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_ジャンル名が重複する場合はジャンルを登録できない(): void
    {
        $user = User::factory()->create();

        Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $this->actingAs($user)
            ->post(route('genres.store'), [
                'name' => 'ミステリー',
            ])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseCount('genres', 1);
    }
}
