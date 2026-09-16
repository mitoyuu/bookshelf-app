<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReadingPlanRequest extends FormRequest
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
            'book_id' => ['required', 'exists:books,id',
                Rule::unique('reading_plans', 'book_id')
                    ->where(function ($query) {
                        $query->where('user_id', auth()->id())
                            ->where('status', ReadingPlanStatus::InProgress->value);
                    }),
            ],
            'target_date' => ['required', 'date'],
        ];
    }
}
