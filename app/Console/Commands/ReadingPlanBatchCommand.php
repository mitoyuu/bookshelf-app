<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ReadingPlanBatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reading-plan:batch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '読書計画の期限切れ処理とリマインダー通知を実行する';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        DB::transaction(function (): void {
            $today = Carbon::today();

            $this->expireOverduePlans($today);
            $this->sendThreeDaysBeforeNotifications($today);
            $this->sendDueDateNotifications($today);
            $this->sendThreeDaysAfterNotifications($today);
        });

        return self::SUCCESS;
    }

    /**
     * 期限を過ぎた読書計画を期限切れに変更する。
     */
    private function expireOverduePlans(Carbon $today): void
    {
        ReadingPlan::query()
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', '<', $today)
            ->update([
                'status' => ReadingPlanStatus::Expired,
            ]);
    }

    /**
     * 期日の3日前の読書計画に通知を送る。
     */
    private function sendThreeDaysBeforeNotifications(Carbon $today): void
    {
        $targetDate = $today->copy()->addDays(3);

        $plans = ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', $targetDate)
            ->get();

        foreach ($plans as $plan) {
            $this->sendNotification(
                $plan,
                'three_days_before',
                '読書計画リマインド — 期限まであと3日',
                "『{$plan->book->title}』の期限まで残り3日です。\n引き続き読書を進めましょう。",
            );
        }
    }

    /**
     * 期日当日の読書計画に通知を送る。
     */
    private function sendDueDateNotifications(Carbon $today): void
    {
        $plans = ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', ReadingPlanStatus::InProgress)
            ->whereDate('target_date', $today)
            ->get();

        foreach ($plans as $plan) {
            $this->sendNotification(
                $plan,
                'on_due_date',
                '読書計画 — 本日が期限',
                "『{$plan->book->title}』は本日が期限です。\n読了済みなら完了登録を、もう少し必要なら期限を変更してください。",
            );
        }
    }

    /**
     * 期日の3日後の読書計画に再エンゲージメント通知を送る。
     */
    private function sendThreeDaysAfterNotifications(Carbon $today): void
    {
        $targetDate = $today->copy()->subDays(3);

        $plans = ReadingPlan::query()
            ->with(['user', 'book'])
            ->where('status', ReadingPlanStatus::Expired)
            ->whereDate('target_date', $targetDate)
            ->get();

        foreach ($plans as $plan) {
            $this->sendNotification(
                $plan,
                'three_days_after',
                '読書計画 — 期限超過3日経過',
                "『{$plan->book->title}』の期限から3日が経過しました。\n読了済みなら完了登録、続けるなら期限を変更してください。",
            );
        }
    }

    /**
     * 読書計画に対して通知を1件送る。
     *
     * 同じ読書計画・同じタイミングの通知が既に存在する場合は送らない。
     */
    private function sendNotification(
        ReadingPlan $plan,
        string $timing,
        string $title,
        string $body,
    ): void {
        $alreadySent = $plan->user
            ->notifications()
            ->where('type', ReadingPlanNotification::class)
            ->where('data->reading_plan_id', $plan->id)
            ->where('data->timing', $timing)
            ->exists();

        if ($alreadySent) {
            return;
        }

        Notification::send(
            $plan->user,
            new ReadingPlanNotification(
                $plan->id,
                $title,
                $body,
                $timing,
            ),
        );
    }
}
