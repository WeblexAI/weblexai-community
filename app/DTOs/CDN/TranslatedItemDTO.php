<?php

namespace App\DTOs\CDN;

use Illuminate\Support\Collection;

class TranslatedItemDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $text,
        public readonly string $translated,
        public readonly string $source,
        public readonly ?int $translationId = null,
        public readonly string $type = 'text',
        public readonly string $attr = '',
        public readonly string $context = '',
    ) {}

    public function asArray(): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'translated' => $this->translated,
            'type' => $this->type,
            'attr' => $this->attr,
            'context' => $this->context,
        ];
    }

    public static function toArray(self|Collection|array $items): array
    {
        if ($items instanceof self) {
            return $items->asArray();
        }

        if ($items instanceof Collection) {
            return $items->map(fn (self $dto) => $dto->asArray())->toArray();
        }

        return array_map(fn (self $dto) => $dto->asArray(), $items);
    }

    public function wasFromCache(): bool
    {
        return $this->source === 'cache';
    }

    public function wasFromDatabase(): bool
    {
        return $this->source === 'database';
    }

    public function wasTranslatedByNmt(): bool
    {
        return $this->source === 'nmt';
    }
}
