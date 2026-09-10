<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. ユーザーをメールアドレスで確実に取得
        $yamada = User::where('email', 'yamada@example.com')->first();
        $suzuki = User::where('email', 'suzuki@example.com')->first();

        // 2. 書籍のIDを6冊分取得する（book_idが重複しないようにするため）
        $bookIds = Book::pluck('id')->take(6)->all();

        // --- 山田太郎（主要シナリオ集約、ID 1〜5） ---

        // 1. target_date = 3日後 / status = 'in_progress'（3日前リマインダー対象）
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $bookIds[0],
            'target_date' => Carbon::today()->addDays(3),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        // 2. target_date = 今日 / status = 'in_progress'（当日リマインダー対象）
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $bookIds[1],
            'target_date' => Carbon::today(),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        // 3. target_date = 3日前 / status = 'in_progress'（Auto-expire化 + 3日後再エンゲージメント対象）
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $bookIds[2],
            'target_date' => Carbon::today()->subDays(3),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        // 4. target_date = 7日後 / status = 'in_progress'（リマインダー対象外）
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $bookIds[3],
            'target_date' => Carbon::today()->addDays(7),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        // 5. target_date = 10日前 / status = 'completed' / completed_at = 5日前（完了済み）
        ReadingPlan::create([
            'user_id' => $yamada->id,
            'book_id' => $bookIds[4],
            'target_date' => Carbon::today()->subDays(10),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => Carbon::today()->subDays(5),
        ]);

        // --- 鈴木花子（他ユーザー認可テスト用、ID 6） ---

        // 6. target_date = 5日後 / status = 'in_progress'（他ユーザーの認可テスト用）
        ReadingPlan::create([
            'user_id' => $suzuki->id,
            'book_id' => $bookIds[5],
            'target_date' => Carbon::today()->addDays(5),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);
    }
}
