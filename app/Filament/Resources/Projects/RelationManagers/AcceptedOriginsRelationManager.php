<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Filament\Concerns\LogsAdminActivity;
use App\Models\ProjectAcceptedOrigin;
use App\Support\OriginNormalizer;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class AcceptedOriginsRelationManager extends RelationManager
{
    use LogsAdminActivity;

    protected static string $relationship = 'acceptedOrigins';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()?->can('view', $ownerRecord) ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('origin')
                ->placeholder('https://example.com')
                ->required()
                ->rule(function () {
                    return function (string $attribute, mixed $value, \Closure $fail): void {
                        try {
                            app(OriginNormalizer::class)->normalize((string) $value);
                        } catch (InvalidArgumentException $exception) {
                            $fail($exception->getMessage());
                        }
                    };
                }),
        ]);
    }

    public function table(Table $table): Table
    {
        $normalize = function (array $data): array {
            $normalized = app(OriginNormalizer::class)->normalize($data['origin']);
            $data['origin'] = $normalized;
            $data['normalized_origin'] = $normalized;

            return $data;
        };

        return $table
            ->recordTitleAttribute('origin')
            ->columns([
                TextColumn::make('origin')->copyable()->searchable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('update', $this->getOwnerRecord()) ?? false)
                    ->mutateDataUsing($normalize)
                    ->after(function (ProjectAcceptedOrigin $record): void {
                        $this->logAdminActivity(
                            description: sprintf(
                                'Added accepted origin "%s" to project "%s".',
                                $record->origin,
                                $this->getOwnerRecord()->name,
                            ),
                            subject: $record,
                            properties: [
                                'project' => $this->getActivityModelSnapshot($this->getOwnerRecord()),
                            ],
                            event: 'created',
                        );
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('update', $this->getOwnerRecord()) ?? false)
                    ->mutateDataUsing($normalize)
                    ->after(function (ProjectAcceptedOrigin $record): void {
                        $this->logAdminActivity(
                            description: sprintf(
                                'Updated accepted origin "%s" for project "%s".',
                                $record->origin,
                                $this->getOwnerRecord()->name,
                            ),
                            subject: $record,
                            properties: [
                                'project' => $this->getActivityModelSnapshot($this->getOwnerRecord()),
                            ],
                            event: 'updated',
                        );
                    }),
                DeleteAction::make()
                    ->authorize(fn (): bool => auth()->user()?->can('update', $this->getOwnerRecord()) ?? false)
                    ->after(function (ProjectAcceptedOrigin $record): void {
                        $this->logAdminActivity(
                            description: sprintf(
                                'Removed accepted origin "%s" from project "%s".',
                                $record->origin,
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
