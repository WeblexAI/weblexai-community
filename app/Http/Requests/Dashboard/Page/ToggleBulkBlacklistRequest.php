<?php

namespace App\Http\Requests\Dashboard\Page;

use App\Models\Page;
use Illuminate\Foundation\Http\FormRequest;

class ToggleBulkBlacklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('toggleBulkBlacklist', [Page::class, $this->route('project')]);
    }

    public function rules(): array
    {
        return [
            'is_blacklisted' => ['required', 'boolean'],
            'page_ids' => ['required', 'array'],
        ];
    }
}
