<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * お気に入りに登録した書籍一覧を表示する。
     *
     * ログインユーザーがお気に入りに登録した書籍を取得し、
     * ページネーションして表示する。
     *
     * @return View お気に入り書籍一覧画面
     */
    public function index(): View
    {
        $user = request()->user();
        $books = $user->favoriteBooks()->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * 指定した書籍のお気に入り登録状態を切り替える。
     *
     * お気に入りに登録されている場合は解除し、登録されていない場合は登録する。
     *
     * @param  Book  $book  お気に入り状態を切り替える書籍
     * @return RedirectResponse 直前のページへのリダイレクト
     */
    public function toggle(Book $book): RedirectResponse
    {
        $user = request()->user();
        $user->favoriteBooks()->toggle($book->id);

        return back();
    }
}
