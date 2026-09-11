<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    public function index(): View
    {
        // ログイン中のユーザーの読書計画を取得する
        $user = auth()->user();

        $currentStatus = request('status');

        $status = $currentStatus
            ? ReadingPlanStatus::tryFrom($currentStatus)
            : null;

        $readingPlansQuery = $user->readingPlans()
            ->with('book');

        if ($status) {
            $readingPlansQuery->where('status', $status);
        }

        $readingPlans = $readingPlansQuery->get();

        return view(
            'reading-plans.index',
            compact('readingPlans', 'currentStatus')
        );
    }

    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        ReadingPlan::create([
            'user_id' => 1,
            'book_id' => 3,
            'target_date' => today(),
            'status' => ReadingPlanStatus::InProgress,
        ]);
    }
}
