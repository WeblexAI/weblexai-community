<?php

namespace App\Services;

class ProviderBatchService
{
    /** @return array<int, array<string, mixed>> */
    public function split(array $items, int $limit = 2000): array
    {
        $parts = [];
        foreach ($items as $item) {
            $text = $item['provider_text'] ?? $item['text'];
            if (mb_strlen($text) <= $limit) {
                $parts[] = [...$item, 'parent_id' => (string) $item['id'], 'part' => 0];

                continue;
            }

            $remaining = $text;
            $part = 0;
            while ($remaining !== '') {
                $cut = min($limit, mb_strlen($remaining));
                if ($cut < mb_strlen($remaining)) {
                    $candidate = mb_substr($remaining, 0, $cut);
                    $boundary = max(mb_strrpos($candidate, "\n"), mb_strrpos($candidate, ' '), mb_strrpos($candidate, '.'));
                    if ($boundary > 0) {
                        $cut = $boundary + 1;
                    }
                    preg_match_all('/GLS[0-9A-HJKMNP-TV-Z]{26}/', $remaining, $matches);
                    foreach ($matches[0] as $token) {
                        $offset = mb_strpos($remaining, $token);
                        $tokenEnd = $offset + mb_strlen($token);
                        if ($offset < $cut && $cut < $tokenEnd) {
                            $cut = $offset;
                        }
                    }
                }
                if ($cut === 0) {
                    throw new \RuntimeException('A glossary placeholder exceeds the provider batch limit.');
                }
                $parts[] = [
                    ...$item,
                    'id' => '__weblex_part_'.hash('sha256', (string) $item['id']).'_'.($part++),
                    'provider_text' => mb_substr($remaining, 0, $cut),
                    'parent_id' => (string) $item['id'],
                    'part' => $part - 1,
                ];
                $remaining = mb_substr($remaining, $cut);
            }
        }

        return $parts;
    }

    /** @return array<int, array<string, mixed>> */
    public function reassemble(array $parts, array $translated): array
    {
        $byId = collect($translated)->keyBy(fn (array $item): string => (string) $item['id']);
        $result = [];
        foreach (collect($parts)->groupBy('parent_id') as $parentId => $group) {
            $first = $group->sortBy('part')->first();
            $output = '';
            foreach ($group->sortBy('part') as $part) {
                $translation = $byId->get((string) $part['id']);
                if (! $translation) {
                    throw new \RuntimeException('The translation provider returned an incomplete response.');
                }
                $output .= $translation['translated'];
            }
            $result[] = [
                'id' => $parentId,
                'text' => $first['text'],
                'translated' => $output,
                'type' => $first['type'] ?? 'text',
                'attr' => $first['attr'] ?? '',
                'context' => $first['context'] ?? '',
            ];
        }

        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    public function completed(array $parts, array $translated, array $emitted): array
    {
        $translatedIds = collect($translated)->pluck('id')->map(fn (mixed $id): string => (string) $id)->flip();
        $completeParts = collect($parts)->groupBy('parent_id')->filter(
            fn ($group, string $parentId): bool => ! isset($emitted[$parentId])
                && $group->every(fn (array $part): bool => isset($translatedIds[(string) $part['id']])),
        )->flatten(1)->all();

        return $completeParts === [] ? [] : $this->reassemble($completeParts, $translated);
    }

    /** @return array<int, array<int, array<string, mixed>>> */
    public function batches(array $parts, int $maxCharacters, int $maxItems = 100, ?int $maxOutputTokens = null): array
    {
        $batches = [];
        $batch = [];
        $characters = 0;
        $estimatedOutputTokens = 0;
        foreach ($parts as $part) {
            $length = mb_strlen($part['provider_text'] ?? $part['text']);
            $estimatedTokens = (int) ceil(((4 * $length) + strlen((string) $part['id']) + 32) / 4);
            if ($batch !== [] && (count($batch) >= $maxItems || $characters + $length > $maxCharacters || ($maxOutputTokens !== null && $estimatedOutputTokens + $estimatedTokens > $maxOutputTokens))) {
                $batches[] = $batch;
                $batch = [];
                $characters = 0;
                $estimatedOutputTokens = 0;
            }
            $batch[] = $part;
            $characters += $length;
            $estimatedOutputTokens += $estimatedTokens;
        }
        if ($batch !== []) {
            $batches[] = $batch;
        }

        return $batches;
    }
}
