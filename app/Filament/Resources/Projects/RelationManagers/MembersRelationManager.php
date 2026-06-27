<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Enums\CollaboratorRole;
use App\Enums\ModelStatus;
use App\Filament\Concerns\LogsAdminActivity;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MembersRelationManager extends RelationManager
{
    use LogsAdminActivity;

    protected static string $relationship = 'collaborators';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('view', $ownerRecord) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('pivot.role')->label('Project role')->badge(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('manageCollaborators', $this->getOwnerRecord()) ?? false)
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query
                        ->whereKeyNot($this->getOwnerRecord()->user_id)
                        ->where('is_active', ModelStatus::ACTIVE->value))
                    ->recordSelectSearchColumns(['name', 'email'])
                    ->preloadRecordSelect()
                    ->schema(fn (AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Select::make('role')
                            ->options(CollaboratorRole::assignable())
                            ->required(),
                    ])
                    ->after(function (User $record, array $data): void {
                        $this->logAdminActivity(
                            description: sprintf(
                                'Added user "%s" to project "%s" as %s.',
                                $record->email,
                                $this->getOwnerRecord()->name,
                                CollaboratorRole::assignable()[$data['role']],
                            ),
                            subject: $record,
                            properties: [
                                'project' => $this->getActivityModelSnapshot($this->getOwnerRecord()),
                                'project_role' => $data['role'],
                            ],
                            event: 'created',
                        );
                    }),
            ])
            ->recordActions([
                Action::make('changeRole')
                    ->authorize(fn (): bool => auth()->user()?->can('manageCollaborators', $this->getOwnerRecord()) ?? false)
                    ->schema([
                        Select::make('role')
                            ->options(CollaboratorRole::assignable())
                            ->required(),
                    ])
                    ->fillForm(fn (User $record): array => [
                        'role' => $record->pivot->role->value,
                    ])
                    ->action(function (User $record, array $data): void {
                        $this->getOwnerRecord()
                            ->collaborators()
                            ->updateExistingPivot($record->id, ['role' => $data['role']]);

                        $this->logAdminActivity(
                            description: sprintf(
                                'Changed user "%s" role in project "%s" to %s.',
                                $record->email,
                                $this->getOwnerRecord()->name,
                                CollaboratorRole::assignable()[$data['role']],
                            ),
                            subject: $record,
                            properties: [
                                'project' => $this->getActivityModelSnapshot($this->getOwnerRecord()),
                                'project_role' => $data['role'],
                            ],
                            event: 'updated',
                        );
                    }),
                DetachAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('manageCollaborators', $this->getOwnerRecord()) ?? false)
                    ->after(function (User $record): void {
                        $this->logAdminActivity(
                            description: sprintf(
                                'Removed user "%s" from project "%s".',
                                $record->email,
                                $this->getOwnerRecord()->name,
                            ),
                            subject: $record,
                            properties: [
                                'project' => $this->getActivityModelSnapshot($this->getOwnerRecord()),
                            ],
                            event: 'deleted',
                        );
                    }),
            ]);
    }
}
