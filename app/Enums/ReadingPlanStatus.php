<?php

namespace App\Enums;

enum ReadingPlanStatus: string
{
    case Expired = 'expired';
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Expired => '期限切れ',
            self::InProgress => '進行中',
            self::Completed => '完了',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Expired => 'expired',
            self::InProgress => 'in_progress',
            self::Completed => 'completed',
        };
    }
}
