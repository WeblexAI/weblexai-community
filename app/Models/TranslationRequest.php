<?php

namespace App\Models;

use App\Enums\ModelStatus;
use App\Traits\BaseModelTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $uuid
 * @property string $created_at
 * @property string $updated_at
 * @property ModelStatus $is_active
 * @property int $created_by_id
 * @property User $creator
 * @property int $page_id
 * @property Page $page
 * @property int $project_id
 * @property Project $project
 * @property int $source_lang_id
 * @property Language $sourceLanguage
 * @property int $target_lang_id
 * @property Language $targetLanguage
 * @property string $ip
 * @property string $country
 */
class TranslationRequest extends Model
{
    use BaseModelTrait, MassPrunable;

    public function prunable(): Builder
    {
        return $this->newQuery()->where('created_at', '<', now()->subMonthsNoOverflow(1)->subDay());
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function sourceLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'source_lang_id');
    }

    public function targetLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'target_lang_id');
    }
}
