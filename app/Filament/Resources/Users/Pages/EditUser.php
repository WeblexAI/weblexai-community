<?php

namespace App\Filament\Resources\Users\Pages;

use App\Enums\ModelStatus;
use App\Enums\UserRole;
use App\Filament\Concerns\LogsAdminActivity;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class EditUser extends EditRecord
{
    use LogsAdminActivity;

    protected static string $resource = UserResource::class;

    private ?int $selectedRoleId = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('resetPassword')
                ->schema([
                    TextInput::make('password')
                        ->password()
                        ->confirmed()
                        ->rule(Password::defaults())
                        ->required(),
                    TextInput::make('password_confirmation')
                        ->password()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'password' => $data['password'],
                        'force_password_change' => true,
                    ]);
                    $this->logAdminActivity(
                        description: sprintf('Reset password for user "%s".', $this->record->email),
                        subject: $this->record,
                        event: 'updated',
                    );
                    Notification::make()
                        ->title('Password reset')
                        ->body('The user must change this temporary password after signing in.')
                        ->success()
                        ->send();
                }),
            ViewAction::make(),
            DeleteAction::make()
                ->after(function (): void {
                    $this->logAdminActivity(
                        description: sprintf('Deleted user "%s".', $this->record->email),
                        subject: $this->record,
                        event: 'deleted',
                    );
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->selectedRoleId = isset($data['system_role']) ? (int) $data['system_role'] : null;
        unset($data['system_role']);

        $selectedRole = $this->selectedRoleId ? Role::query()->find($this->selectedRoleId) : null;

        $deactivatingLastAdmin = $this->record->hasRole('admin')
            && (int) $data['is_active'] === ModelStatus::INACTIVE->value
            && User::role('admin')->where('is_active', ModelStatus::ACTIVE->value)->count() <= 1;

        if ($deactivatingLastAdmin) {
            throw ValidationException::withMessages([
                'data.is_active' => 'The last active administrator cannot be deactivated.',
            ]);
        }

        $demotingLastAdmin = $this->record->hasRole(UserRole::ADMIN->value)
            && $selectedRole?->name !== UserRole::ADMIN->value
            && User::role(UserRole::ADMIN->value)->where('is_active', ModelStatus::ACTIVE->value)->count() <= 1;

        if ($demotingLastAdmin) {
            throw ValidationException::withMessages([
                'data.system_role' => 'The last active administrator must keep the admin role.',
            ]);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->selectedRoleId) {
            $role = Role::query()->find($this->selectedRoleId);

            if ($role) {
                $this->record->syncRoles([$role->name]);
            }
        }

        $this->logAdminActivity(
            description: sprintf('Updated user "%s".', $this->record->email),
            subject: $this->record,
            event: 'updated',
        );
    }
}
