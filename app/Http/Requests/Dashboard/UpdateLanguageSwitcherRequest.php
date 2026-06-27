<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\SwitcherDeviceType;
use App\Models\LanguageSwitcherConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLanguageSwitcherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', [LanguageSwitcherConfig::class, $this->project]);
    }

    public function rules(): array
    {
        return [
            'target_parent_selector' => ['nullable', 'string', 'max:255'],
            'should_display_name' => ['required', 'boolean'],
            'should_display_full_name' => ['required', 'boolean'],
            'should_display_flag' => ['required', 'boolean'],
            'size' => ['required', 'integer', 'min:20', 'max:100'],
            'should_open_on_hover' => ['required', 'boolean'],
            'should_close_on_outside_click' => ['required', 'boolean'],
            'should_show_by_device' => ['required', 'boolean'],
            'preferred_device' => [
                Rule::requiredIf($this->boolean('should_show_by_device')),
                Rule::enum(SwitcherDeviceType::class),
            ],
            'device_pixel_breakpoint' => [
                Rule::requiredIf($this->boolean('should_show_by_device')),
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }
}
