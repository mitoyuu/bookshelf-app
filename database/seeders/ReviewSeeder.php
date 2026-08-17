<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 事前にユーザーと書籍を取得してキーを紐付けやすくする
        $users = User::all()->keyBy('email');
        $books = Book::all()->keyBy('title');

        // 要件：5人のユーザーが11冊に対して計32件のレビューを投稿
        // （各書籍に2〜4件、ratingは3〜5、具体的なコメント）
        $reviews = [
            // 1冊目：吾輩は猫である
            [
                'email' => 'yamada@example.com',
                'title' => '吾輩は猫である',
                'rating' => 5,
                'comment' => '日本文学の傑作。猫の視点から人間社会を風刺する手法が秀逸です。',
            ],
            [
                'email' => 'suzuki@example.com',
                'title' => '吾輩は猫である',
                'rating' => 4,
                'comment' => '古典的な作品ですが、今読んでも面白い。文体に慣れるまで少し時間がかかりました。',
            ],
            [
                'email' => 'tanaka@example.com',
                'title' => '吾輩は猫である',
                'rating' => 5,
                'comment' => '何度読んでも新しい発見がある名作です。',
            ],

            // 2冊目：人を動かす
            [
                'email' => 'yamada@example.com',
                'title' => '人を動かす',
                'rating' => 5,
                'comment' => '人との接し方を改めて考えさせられる一冊でした。',
            ],
            [
                'email' => 'sato@example.com',
                'title' => '人を動かす',
                'rating' => 4,
                'comment' => '仕事だけでなく日常生活にも役立つ内容が多かったです。',
            ],
            [
                'email' => 'takahashi@example.com',
                'title' => '人を動かす',
                'rating' => 3,
                'comment' => '基本的な内容が中心ですが、改めて学ぶ価値がありました。',
            ],

            // 3冊目：リーダブルコード
            [
                'email' => 'yamada@example.com',
                'title' => 'リーダブルコード',
                'rating' => 5,
                'comment' => 'すぐに実践できる内容が多く、日々の開発に役立ちました。',
            ],
            [
                'email' => 'sato@example.com',
                'title' => 'リーダブルコード',
                'rating' => 4,
                'comment' => 'コードを書く前に読んでおきたい一冊だと感じました。',
            ],
            [
                'email' => 'takahashi@example.com',
                'title' => 'リーダブルコード',
                'rating' => 5,
                'comment' => 'チーム開発を意識した考え方がとても参考になりました。',
            ],

            // 4冊目：7つの習慣
            [
                'email' => 'suzuki@example.com',
                'title' => '7つの習慣',
                'rating' => 5,
                'comment' => '人生や仕事に対する考え方を見直すきっかけになりました。何度も読み返したい一冊です。',
            ],
            [
                'email' => 'sato@example.com',
                'title' => '7つの習慣',
                'rating' => 4,
                'comment' => '具体的な考え方や行動方法が紹介されていて、日常生活でも意識して取り入れたいと思いました。',
            ],
            [
                'email' => 'takahashi@example.com',
                'title' => '7つの習慣',
                'rating' => 5,
                'comment' => '仕事での人間関係や目標設定について深く考えられる内容で、とても参考になりました。',
            ],

            // 5冊目：坊っちゃん
            [
                'email' => 'yamada@example.com',
                'title' => '坊っちゃん',
                'rating' => 4,
                'comment' => '主人公の正義感あふれる性格が魅力的で、テンポよく最後まで楽しめました。',
            ],
            [
                'email' => 'tanaka@example.com',
                'title' => '坊っちゃん',
                'rating' => 5,
                'comment' => '昔の作品ですが、人間関係や登場人物の描写が面白く、今でも楽しめる作品だと思いました。',
            ],
            [
                'email' => 'sato@example.com',
                'title' => '坊っちゃん',
                'rating' => 4,
                'comment' => '夏目漱石らしい表現が楽しめました。短く読みやすいので文学初心者にもおすすめです。',
            ],

            // 6冊目：サピエンス全史
            [
                'email' => 'suzuki@example.com',
                'title' => 'サピエンス全史',
                'rating' => 5,
                'comment' => '人類の歴史を大きな視点で学ぶことができ、世界の見方が変わる一冊でした。',
            ],
            [
                'email' => 'takahashi@example.com',
                'title' => 'サピエンス全史',
                'rating' => 4,
                'comment' => '歴史の出来事を単なる年表ではなく、社会の変化として理解できる点が面白かったです。',
            ],
            [
                'email' => 'tanaka@example.com',
                'title' => 'サピエンス全史',
                'rating' => 5,
                'comment' => '壮大なテーマですが説明が分かりやすく、人類について深く考えさせられる内容でした。',
            ],

            // 7冊目：Clean Code
            [
                'email' => 'yamada@example.com',
                'title' => 'Clean Code',
                'rating' => 5,
                'comment' => '読みやすいコードを書くための考え方が整理されていて、プログラミング学習に役立ちました。',
            ],
            [
                'email' => 'takahashi@example.com',
                'title' => 'Clean Code',
                'rating' => 4,
                'comment' => 'コードを書く時に意識すべきポイントが具体的で、実務でも活かせそうな内容でした。',
            ],
            [
                'email' => 'suzuki@example.com',
                'title' => 'Clean Code',
                'rating' => 5,
                'comment' => '保守しやすいコードとは何かを学ぶことができ、エンジニア必読の本だと感じました。',
            ],

            // 8冊目：嫌われる勇気
            [
                'email' => 'sato@example.com',
                'title' => '嫌われる勇気',
                'rating' => 5,
                'comment' => 'アドラー心理学を対話形式で学べるので読みやすく、考え方の変化につながりました。',
            ],
            [
                'email' => 'tanaka@example.com',
                'title' => '嫌われる勇気',
                'rating' => 4,
                'comment' => '人間関係の悩みに対する新しい視点を得ることができました。',
            ],
            [
                'email' => 'yamada@example.com',
                'title' => '嫌われる勇気',
                'rating' => 3,
                'comment' => '考え方は興味深かったですが、実践するには少し難しい部分もあると感じました。',
            ],

            // 9冊目：火花
            [
                'email' => 'yamada@example.com',
                'title' => '火花',
                'rating' => 4,
                'comment' => '芸人の世界や人間関係の葛藤がリアルに描かれていて、考えさせられる作品でした。',
            ],
            [
                'email' => 'suzuki@example.com',
                'title' => '火花',
                'rating' => 5,
                'comment' => '夢を追うことの難しさや人とのつながりについて深く考えられる小説でした。',
            ],
            [
                'email' => 'tanaka@example.com',
                'title' => '火花',
                'rating' => 4,
                'comment' => '登場人物の感情表現が細かく描かれていて、最後まで引き込まれました。',
            ],

            // 10冊目：FACTFULNESS
            [
                'email' => 'sato@example.com',
                'title' => 'FACTFULNESS',
                'rating' => 5,
                'comment' => '思い込みではなくデータをもとに世界を見る大切さを学べる内容でした。',
            ],
            [
                'email' => 'takahashi@example.com',
                'title' => 'FACTFULNESS',
                'rating' => 4,
                'comment' => '普段のニュースの見方が変わるきっかけになり、物事を客観的に考える大切さを感じました。',
            ],
            [
                'email' => 'yamada@example.com',
                'title' => 'FACTFULNESS',
                'rating' => 5,
                'comment' => '世界に対する誤った認識に気付かされる内容で、とても勉強になりました。',
            ],

            // 11冊目：コンテナ物語
            [
                'email' => 'suzuki@example.com',
                'title' => 'コンテナ物語',
                'rating' => 4,
                'comment' => '普段意識しない物流の仕組みが世界経済に大きな影響を与えていることを学べました。',
            ],
            [
                'email' => 'tanaka@example.com',
                'title' => 'コンテナ物語',
                'rating' => 5,
                'comment' => 'コンテナという身近な存在から産業や経済の変化を理解できる興味深い本でした。',
            ],
        ];

        foreach ($reviews as $data) {
            Review::create([
                'user_id' => $users[$data['email']]->id,
                'book_id' => $books[$data['title']]->id,
                'rating' => $data['rating'],
                'comment' => $data['comment'],
            ]);
        }
    }
}
