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
     * 読書計画一覧を表示する。
     *
     * ログインユーザーの読書計画を取得し、指定されたステータスがある場合は絞り込んで表示する。
     *
     * @return View 読書計画一覧画面
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
     * 読書計画作成画面を表示する。
     *
     * @return View 読書計画作成画面
     */
    public function create(): View
    {
        $books = Book::all();

        return view('reading-plans.create', compact('books'));
    }

    /**
     * 新しい読書計画を作成する。
     *
     * 作成時のステータスは進行中とし、完了日時は未設定にする。
     *
     * @param  StoreReadingPlanRequest  $request  読書計画作成リクエスト
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
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
     * 指定した読書計画の編集画面を表示する。
     *
     * @param  ReadingPlan  $readingPlan  編集対象の読書計画
     * @return View 読書計画編集画面
     */
    public function edit(ReadingPlan $readingPlan): View
    {
        $this->authorize('update', $readingPlan);

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 指定した読書計画を更新する。
     *
     * Policyによる認可を行ったうえで、読書計画の期日を更新する。
     *
     * @param  UpdateReadingPlanRequest  $request  読書計画更新リクエスト
     * @param  ReadingPlan  $readingPlan  更新対象の読書計画
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
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
     * 指定した読書計画を削除する。
     *
     * Policyによる認可を行ったうえで、読書計画を削除する。
     *
     * @param  ReadingPlan  $readingPlan  削除対象の読書計画
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
     */
    public function destroy(ReadingPlan $readingPlan): RedirectResponse
    {
        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')->with('success', '読書計画を削除しました。');
    }

    /**
     * 指定した読書計画を完了状態にする。
     *
     * ステータスを完了に変更し、完了日時を記録する。
     *
     * @param  ReadingPlan  $readingPlan  完了対象の読書計画
     * @return RedirectResponse 読書計画一覧画面へのリダイレクト
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
