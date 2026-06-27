<?php

namespace App\Filament\Resources\AdminActivities;

use App\Filament\Resources\AdminActivities\Pages\ListAdminActivities;
use App\Filament\Resources\AdminActivities\Tables\AdminActivitiesTable;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdminActivityResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Admin Activity';

    protected static ?string $modelLabel = 'Admin activity';

    protected static ?string $pluralModelLabel = 'Admin activities';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return AdminActivitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminActivities::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('log_name', 'admin')
            ->with(['causer', 'subject']);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
