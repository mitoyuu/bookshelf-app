<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(): View
    {
        $user = request()->user();
        $books = $user->favoriteBooks()->paginate(10);

        return view('favorites.index', compact('books'));
    }

    // お気に入りを切り替える（登録されていれば解除、なければ登録）
    public function toggle(Book $book)
    {
        $user = request()->user();
        $user->favoriteBooks()->toggle($book->id);

        return back();
    }
}
