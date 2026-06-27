<?php

namespace App\Models;

use App\Enums\GlossaryRule;
use App\Enums\ModelStatus;
use App\Observers\GlossaryObserver;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property string $text
 * @property string|null $translated
 * @property string $project_id
 * @property ModelStatus $is_active
 * @property string $created_at
 * @property string $updated_at
 * @property Project $project
 * @property int $created_by_id
 * @property User $creator
 * @property string $placeholder
 * @property bool $is_case_sensitive
 * @property bool $is_all_languages
 * @property GlossaryRule $rule
 * @property Collection<Language> $languages
 */
#[ObservedBy(GlossaryObserver::class)]
class Glossary extends Model
{
    use BaseModelTrait, HasFactory, LogsActivity;

    protected $casts = [
        'rule' => GlossaryRule::class,
        'is_case_sensitive' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->setDescriptionForEvent(function ($event) {
                $rule = is_string($this->rule) ? $this->rule : $this->rule->value;

                return (auth()->user()?->name ?? 'System')." has {$event} glossary rule {$rule} \"{$this->text}\"";
            });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'glossary_language');
    }

    #[Scope]
    protected function whereAppliesTo(Builder $query, string $content, bool $isCaseSensitive = false): void
    {
        $preparedContent = $isCaseSensitive ? $content : mb_strtolower($content);

        // Add a cheap STRPOS check before regex
        $query->where(function ($q) use ($preparedContent, $isCaseSensitive) {
            if ($isCaseSensitive) {
                $q->whereRaw('STRPOS(?, "text") > 0', [$preparedContent])
                    ->whereRaw("? ~ CONCAT('\\y', \"text\", '\\y')", [$preparedContent]);
            } else {
                $q->whereRaw('STRPOS(?, LOWER("text")) > 0', [$preparedContent])
                    ->whereRaw("? ~ CONCAT('\\y', LOWER(\"text\"), '\\y')", [$preparedContent]);
            }
        });
    }

    #[Scope]
    protected function whereTextContains(Builder $query, string $text, bool $isCaseSensitive = false): void
    {
        if ($isCaseSensitive) {
            $query->whereRaw('"text" LIKE ?', ["%{$text}%"]);
        } else {
            $query->whereRaw('"text" ILIKE ?', ["%{$text}%"]);
        }
    }
}
