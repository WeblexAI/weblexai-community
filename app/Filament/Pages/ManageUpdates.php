<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\LogsAdminActivity;
use App\Updates\ReleaseFeed;
use App\Updates\UpdateManager;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;
use UnitEnum;

class ManageUpdates extends Page
{
    use LogsAdminActivity;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Updates';

    protected static ?string $slug = 'updates';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.manage-updates';

    public ?array $release = null;

    public ?string $error = null;

    public function mount(ReleaseFeed $feed): void
    {
        $this->loadRelease($feed);
    }

    public function check(ReleaseFeed $feed): void
    {
        $this->loadRelease($feed, true);
    }

    public function apply(ReleaseFeed $feed, UpdateManager $updates): void
    {
        try {
            $release = $feed->latest();
            if (! $release || ! $feed->updateAvailable($release) || ! $feed->isCompatible($release)) {
                throw new \RuntimeException('No compatible update is available.');
            }

            $updates->apply($release);
            $this->logAdminActivity(
                description: sprintf('Started update to version "%s".', $release->version),
                properties: [
                    'release_version' => $release->version,
                ],
                event: 'updated',
            );
            Notification::make()->success()->title('Update started')->send();
        } catch (Throwable $exception) {
            $this->logAdminActivity(
                description: 'Failed to start an application update.',
                properties: [
                    'error_type' => $exception::class,
                ],
                event: 'failed',
            );
            Notification::make()->danger()->title('Update failed')->body($exception->getMessage())->send();
        }
    }

    private function loadRelease(ReleaseFeed $feed, bool $force = false): void
    {
        try {
            $release = $feed->latest($force);
            $this->release = $release ? [
                'version' => $release->version,
                'published_at' => $release->publishedAt,
                'notes_url' => $release->notesUrl,
                'security' => $release->security,
                'compatible' => $feed->isCompatible($release),
                'available' => $feed->updateAvailable($release),
            ] : null;
            $this->error = null;
        } catch (Throwable $exception) {
            $this->error = $exception->getMessage();
        }
    }
}
