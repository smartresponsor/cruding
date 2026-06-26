<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Resource;

/**
 * A single named block placed into a resource location.
 */
final readonly class CrudResourceBlock
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $key,
        public string $type,
        public array $data,
        public array $meta = [],
    ) {
    }

    /**
     * @return array{key: string, type: string, data: array<string, mixed>, meta: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'data' => $this->data,
            'meta' => $this->meta,
        ];
    }
}
