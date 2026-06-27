<?php

namespace App\Http\Requests\Dashboard\Language;

use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;

class ToggleLanguageTranslationsPublicityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', [Language::class, $this->route('project')]);
    }

    public function rules(): array
    {
        return [
            'is_public' => ['required', 'boolean'],
        ];
    }
}
