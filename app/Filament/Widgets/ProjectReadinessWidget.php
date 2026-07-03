<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ProjectReadinessWidget extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Project readiness')
            ->description('Projects should have a provider key, accepted origins, and target languages before traffic starts.')
            ->query($this->query())
            ->defaultSort('updated_at', 'desc')
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10, 25])
            ->emptyStateHeading('No projects have been created yet.')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->url(fn (Project $record): string => ProjectResource::getUrl('view', ['record' => $record])),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable(),
                TextColumn::make('providerCredential.provider_label')
                    ->label('Provider')
                    ->placeholder('Missing')
                    ->badge()
                    ->color(fn (?string $state): string => $state ? 'success' : 'danger'),
                TextColumn::make('accepted_origins_count')
                    ->label('Origins')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('languages_count')
                    ->label('Languages')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('translations_count')
                    ->label('Strings')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('readiness')
                    ->label('Readiness')
                    ->state(fn (Project $record): string => $this->readinessLabel($record))
                    ->badge()
                    ->color(fn (Project $record): string => $this->readinessColor($record)),
                TextColumn::make('next_step')
                    ->label('Next step')
                    ->state(fn (Project $record): string => $this->nextStep($record))
                    ->wrap(),
            ]);
    }

    private function query(): Builder
    {
        return Project::query()
            ->with(['owner', 'providerCredential'])
            ->withCount(['acceptedOrigins', 'languages', 'translations']);
    }

    private function readinessLabel(Project $project): string
    {
        $missing = $this->missingSetup($project);

        return count($missing) === 0 ? 'Ready' : count($missing).' issue'.(count($missing) === 1 ? '' : 's');
    }

    private function readinessColor(Project $project): string
    {
        return count($this->missingSetup($project)) === 0 ? 'success' : 'warning';
    }

    /**
     * @return array<int, string>
     */
    private function missingSetup(Project $project): array
    {
        return array_values(array_filter([
            $project->provider_credential_id ? null : 'provider key',
            $project->accepted_origins_count > 0 ? null : 'accepted origin',
            $project->languages_count > 0 ? null : 'target language',
        ]));
    }

    private function nextStep(Project $project): string
    {
        $missing = $this->missingSetup($project);

        return count($missing) === 0
            ? 'Monitor traffic and review extracted translations.'
            : 'Add '.implode(', ', $missing).'.';
    }
}
