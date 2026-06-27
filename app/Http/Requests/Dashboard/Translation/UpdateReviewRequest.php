<?php

namespace App\Http\Requests\Dashboard\Translation;

use App\Models\Translation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', [Translation::class, $this->route('project')]);
    }

    public function rules(): array
    {
        return [
            'translation_id' => ['required', Rule::exists('translations', 'id')],
            'is_reviewed' => ['required', 'boolean'],
        ];
    }
}
