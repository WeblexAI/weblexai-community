<?php

namespace App\Http\Requests\Installation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class InstallApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:100'],
            'app_url' => ['required', 'url:http,https', 'max:255'],
            'app_locale' => ['required', 'alpha_dash', 'max:10'],
            'app_timezone' => ['required', 'timezone'],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ];
    }
}
