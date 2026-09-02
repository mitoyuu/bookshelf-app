<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // キーワード検索（タイトルや著者名などを想定）
            'keyword' => ['nullable', 'string', 'max:255'],

            // ジャンルでの絞り込み（セレクトボックスの選択値：ジャンルID）「すべて」に対応するため nullable
            'genre_id' => ['nullable', 'integer', 'exists:genres,id'],

            // ページネーション用
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
