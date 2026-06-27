<?php

namespace App\Jobs\CDN;

use App\DTOs\CDN\TranslationContext;
use App\Models\Translation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class UpdateTranslationsLastUsedAtJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $translationIds,
        protected string $usedAt,
    ) {}

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('translations-last-used-updater'))->releaseAfter(10),
        ];
    }

    public function handle(): void
    {
        $translationIds = collect($this->translationIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($translationIds->isEmpty()) {
            return;
        }

        $usedAt = Carbon::parse($this->usedAt);
        $staleBefore = $usedAt->copy()->subDays(TranslationContext::LAST_USED_REFRESH_AFTER_DAYS);

        Translation::withoutTimestamps(function () use ($translationIds, $staleBefore, $usedAt) {
            Translation::query()
                ->whereIn('id', $translationIds)
                ->where(function ($query) use ($staleBefore) {
                    $query->whereNull('last_used_at')
                        ->orWhere('last_used_at', '<=', $staleBefore);
                })
                ->update([
                    'last_used_at' => $usedAt,
                ]);
        });
    }
}
