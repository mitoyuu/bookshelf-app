<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all()->keyBy('email');
        $reviews = Review::all()->keyBy('id');

        $reviewLikes = [
            [
                'review_id' => 1,
                'email' => 'suzuki@example.com',
            ],
            [
                'review_id' => 1,
                'email' => 'sato@example.com',
            ],

            [
                'review_id' => 2,
                'email' => 'tanaka@example.com',
            ],

            [
                'review_id' => 3,
                'email' => 'suzuki@example.com',
            ],
            [
                'review_id' => 3,
                'email' => 'sato@example.com',
            ],
            [
                'review_id' => 3,
                'email' => 'takahashi@example.com',
            ],
            [
                'review_id' => 5,
                'email' => 'takahashi@example.com',
            ],
            [
                'review_id' => 6,
                'email' => 'yamada@example.com',
            ],
            [
                'review_id' => 7,
                'email' => 'sato@example.com',
            ],
            [
                'review_id' => 7,
                'email' => 'takahashi@example.com',
            ],
            [
                'review_id' => 9,
                'email' => 'yamada@example.com',
            ],
            [
                'review_id' => 12,
                'email' => 'yamada@example.com',
            ],
            [
                'review_id' => 14,
                'email' => 'yamada@example.com',
            ],
            [
                'review_id' => 18,
                'email' => 'suzuki@example.com',
            ],
            [
                'review_id' => 19,
                'email' => 'suzuki@example.com',
            ],
            [
                'review_id' => 22,
                'email' => 'suzuki@example.com',
            ],
            [
                'review_id' => 22,
                'email' => 'yamada@example.com',
            ],
            [
                'review_id' => 22,
                'email' => 'tanaka@example.com',
            ],
            [
                'review_id' => 26,
                'email' => 'tanaka@example.com',
            ],
        ];

        foreach ($reviewLikes as $data) {
            // 1. メールアドレスからユーザーモデルを取得
            $user = $users[$data['email']];

            // 2. review_idからレビューモデルを取得
            $review = $reviews[$data['review_id']];
            // 自分が書いたレビューではない場合
            if ($user->id !== $review->user_id) {

                // 3. そのレビューに対して、いいねを追加する（すでに登録済みのいいねは消さない）
                $review->likedByUsers()->syncWithoutDetaching([$user->id]);
            }
        }
    }
}
