<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGenreRequest;
use App\Http\Requests\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * ジャンル一覧を表示する。
     *
     * 各ジャンルに紐づく書籍数を取得し、名前順に並べて表示する。
     *
     * @return View ジャンル一覧画面
     */
    public function index(): View
    {
        $genres = Genre::withCount('books')->orderBy('name')->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * ジャンル作成画面を表示する。
     *
     * @return View ジャンル作成画面
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * 新しいジャンルを作成する。
     *
     * @param  StoreGenreRequest  $request  ジャンル作成リクエスト
     * @return RedirectResponse ジャンル一覧画面へのリダイレクト
     */
    public function store(StoreGenreRequest $request): RedirectResponse
    {
        Genre::create($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを作成しました。');
    }

    /**
     * 指定したジャンルと、そのジャンルに紐づく書籍一覧を表示する。
     *
     * @param  Genre  $genre  表示対象のジャンル
     * @return View ジャンル詳細画面
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * ジャンル編集画面を表示する。
     *
     * @param  Genre  $genre  編集対象のジャンル
     * @return View ジャンル編集画面
     */
    public function edit(Genre $genre): View
    {
        return view('genres.edit', compact('genre'));
    }

    /**
     * 指定したジャンルを更新する。
     *
     * @param  UpdateGenreRequest  $request  ジャンル更新リクエスト
     * @param  Genre  $genre  更新対象のジャンル
     * @return RedirectResponse ジャンル一覧画面へのリダイレクト
     */
    public function update(UpdateGenreRequest $request, Genre $genre): RedirectResponse
    {
        $genre->update($request->validated());

        return redirect()->route('genres.index')->with('success', 'ジャンルを更新しました。');
    }

    /**
     * 指定したジャンルを削除する。
     *
     * 書籍が紐づいているジャンルは削除せず、エラーメッセージを表示する。
     *
     * @param  Genre  $genre  削除対象のジャンル
     * @return RedirectResponse ジャンル一覧画面へのリダイレクト
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        if ($genre->books()->exists()) {
            return redirect()->route('genres.index')->with('error', 'このジャンルには書籍が紐づいているため削除できません。');
        }

        $genre->delete();

        return redirect()->route('genres.index')->with('success', 'ジャンルを削除しました。');
    }
}
