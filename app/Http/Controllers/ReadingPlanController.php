<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Models\Book;
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

    public function create(): View
    {
        $books = Book::all();

        return view('reading-plans.create', compact('books'));
    }

    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $user = auth()->user();

        ReadingPlan::create([
            'user_id' => $user->id,
            'book_id' => $request->book_id,
            'target_date' => $request->target_date,
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ]);

        return redirect()->route('reading-plans.index');
    }
}
