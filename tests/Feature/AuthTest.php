<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン画面を表示できる(): void
    {
        $this->get(route('login'))
            ->assertOk();
    }

    public function test_会員登録ができる(): void
    {
        $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);
    }

    public function test_必須項目が未入力の場合は会員登録できない(): void
    {
        $this->post(route('register'), [
            'name' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
        ])
            ->assertSessionHasErrors([
                'name',
                'email',
                'password',
            ]);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_メールアドレスが重複する場合は会員登録できない(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $this->post(route('register'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_正しい情報でログインできる(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ])
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_間違ったパスワードではログインできない(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertSessionHasErrors();

        $this->assertGuest();
    }

    public function test_ログアウトできる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
