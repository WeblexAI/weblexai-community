<?php

namespace App\Http\Requests\Installation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class InstallApplicationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (config('community.deployment_mode') !== 'docker') {
            return;
        }

        $database = config('database.connections.pgsql');
        $redis = config('database.redis.default');

        $this->merge([
            'db_host' => $database['host'],
            'db_port' => $database['port'],
            'db_database' => $database['database'],
            'db_username' => $database['username'],
            'db_password' => $database['password'],
            'redis_host' => $redis['host'],
            'redis_port' => $redis['port'],
            'redis_password' => $redis['password'],
            'redis_db' => $redis['database'],
        ]);
    }

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
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:100'],
            'db_username' => ['required', 'string', 'max:100'],
            'db_password' => ['nullable', 'string', 'max:1000'],
            'redis_host' => ['required', 'string', 'max:255'],
            'redis_port' => ['required', 'integer', 'between:1,65535'],
            'redis_password' => ['nullable', 'string', 'max:1000'],
            'redis_db' => ['required', 'integer', 'between:0,15'],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ];
    }
}
