<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ResetUserPassword extends Command
{
    protected $signature = 'weblex:user:reset-password {email}';

    protected $description = 'Reset a user password and require a change at next login';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user exists with that email address.');

            return self::FAILURE;
        }

        $password = $this->secret('New password');
        $confirmation = $this->secret('Confirm new password');
        $validator = Validator::make(
            ['password' => $password, 'password_confirmation' => $confirmation],
            ['password' => ['required', 'confirmed', Password::defaults()]],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user->forceFill([
            'password' => Hash::make($password),
            'force_password_change' => true,
            'remember_token' => null,
        ])->save();

        $this->info('Password reset. The user must change it after signing in.');

        return self::SUCCESS;
    }
}
