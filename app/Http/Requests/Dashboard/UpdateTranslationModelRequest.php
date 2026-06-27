<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\TranslationAudience;
use App\Enums\TranslationModelType;
use App\Enums\TranslationTone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTranslationModelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');
        $user = $this->user();

        if (! $project || ! $user) {
            return false;
        }

        return $user->can('update', $project);
    }

    public function rules(): array
    {
        $isModelLLM = $this->route('project')
            ?->providerCredential
            ?->provider
            ?->type() === TranslationModelType::LLM;

        return [
            'website_description' => $isModelLLM ? ['required', 'string', 'max:250'] : ['nullable'],
            'translation_tone' => $isModelLLM ? ['required', Rule::enum(TranslationTone::class)] : ['nullable'],
            'translation_audience' => $isModelLLM ? ['required', Rule::enum(TranslationAudience::class)] : ['nullable'],
        ];
    }
}
