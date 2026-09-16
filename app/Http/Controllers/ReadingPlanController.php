<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    /**
     * Display the reading plans.
     */
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

    /**
     * Show the form for creating a reading plan.
     */
    public function create(): View
    {
        $books = Book::all();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * Store a newly created reading plan.
     */
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

        return redirect()->route('reading-plans.index')->with('success', '読書計画を作成しました。');
    }

    /**
     * Show the form for editing a reading plan.
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        $this->authorize('update', $readingPlan);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * Update the specified reading plan.
     */
    public function update(UpdateReadingPlanRequest $request, ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('update', $readingPlan);

        $validated = $request->validated();

        $readingPlan->update([
            'target_date' => $validated['target_date'],
        ]);

        return redirect()->route('reading-plans.index')->with('success', '読書計画を更新しました。');
    }

    /**
     * Remove the specified reading plan.
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました。');
    }

    /**
     * Complete the reading plan.
     */
    public function complete(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('complete', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を完了しました。');
    }
}
