<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * ログインユーザーの通知一覧を表示する。
     *
     * @param  Request  $request  HTTPリクエスト
     * @return View 通知一覧画面
     */
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications
            ->sortByDesc('created_at');

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 指定した通知を既読にする。
     *
     * @param  Request  $request  HTTPリクエスト
     * @param  string  $id  通知ID
     * @return RedirectResponse 通知一覧画面へのリダイレクト
     */
    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return redirect()
            ->route('notifications.index')
            ->with('success', '通知を既読にしました。');
    }
}
