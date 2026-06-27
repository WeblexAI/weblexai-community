<?php

namespace App\Traits;

use App\Models\Language;

trait HasTranslationBatching
{
    protected function packIntoCharBatches(array $items, int $maxChars): array
    {
        $batches = [];
        $current = [];
        $currentCount = 0;

        foreach ($items as $item) {
            $len = mb_strlen($item['text'] ?? '');

            if ($len > $maxChars) {
                if (! empty($current)) {
                    $batches[] = $current;
                    $current = [];
                    $currentCount = 0;
                }
                $batches[] = [$item];

                continue;
            }

            if ($currentCount + $len <= $maxChars) {
                $current[] = $item;
                $currentCount += $len;
            } else {
                if (! empty($current)) {
                    $batches[] = $current;
                }
                $current = [$item];
                $currentCount = $len;
            }
        }

        if (! empty($current)) {
            $batches[] = $current;
        }

        return $batches;
    }

    protected function buildFallbackResult(array $item, Language $source, Language $target): array
    {
        return [
            'id' => $item['id'] ?? null,
            'translated' => null,
            'text' => $item['text'] ?? null,
            'source' => $source->iso_2,
            'target' => $target->iso_2,
            'source_lang_id' => $source->id,
            'target_lang_id' => $target->id,
            'request' => $item,
        ];
    }
}
