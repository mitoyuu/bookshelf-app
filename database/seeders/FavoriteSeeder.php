<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all()->keyBy('email');
        $books = Book::all()->keyBy('title');

        $favorites = [
            // 1人目：山田太郎
            [
                'email' => 'yamada@example.com',
                'book' => '吾輩は猫である',
            ],
            [
                'email' => 'yamada@example.com',
                'book' => '坊っちゃん',
            ],
            [
                'email' => 'yamada@example.com',
                'book' => 'リーダブルコード',
            ],

            // 2人目：鈴木花子
            [
                'email' => 'suzuki@example.com',
                'book' => '人を動かす',
            ],
            [
                'email' => 'suzuki@example.com',
                'book' => '火花',
            ],
            [
                'email' => 'suzuki@example.com',
                'book' => 'FACTFULNESS',
            ],
            [
                'email' => 'suzuki@example.com',
                'book' => 'コンテナ物語',
            ],

            // 3人目：田中一郎
            [
                'email' => 'tanaka@example.com',
                'book' => '7つの習慣',
            ],
            [
                'email' => 'tanaka@example.com',
                'book' => 'サピエンス全史',
            ],
            [
                'email' => 'tanaka@example.com',
                'book' => '嫌われる勇気',
            ],
            [
                'email' => 'tanaka@example.com',
                'book' => '火花',
            ],
            [
                'email' => 'tanaka@example.com',
                'book' => 'コンテナ物語',
            ],

            // 4人目：佐藤美咲
            [
                'email' => 'sato@example.com',
                'book' => '人を動かす',
            ],
            [
                'email' => 'sato@example.com',
                'book' => '吾輩は猫である',
            ],
            [
                'email' => 'sato@example.com',
                'book' => '嫌われる勇気',
            ],

            // 5人目：高橋健太
            [
                'email' => 'takahashi@example.com',
                'book' => 'リーダブルコード',
            ],
            [
                'email' => 'takahashi@example.com',
                'book' => 'Clean Code',
            ],
            [
                'email' => 'takahashi@example.com',
                'book' => 'FACTFULNESS',
            ],
            [
                'email' => 'takahashi@example.com',
                'book' => '火花',
            ],
        ];

        foreach ($favorites as $data) {
            // 1. メールアドレスから、該当する「1人のユーザーモデル」を取り出す
            $user = $users[$data['email']];

            // 2. タイトルから、該当する「本のID」を取り出す
            $bookId = $books[$data['book']]->id;

            // 3. そのユーザーに対して、本を追加する（すでに登録済みの本は消さない）
            $user->favoriteBooks()->syncWithoutDetaching([$bookId]);
        }
    }
}
