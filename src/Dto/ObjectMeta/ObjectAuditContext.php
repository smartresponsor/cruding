<?php

declare(strict_types=1);

namespace App\Cruding\Dto\ObjectMeta;

final readonly class ObjectAuditContext
{
    public function __construct(
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $createdBy,
        public ?string $updatedBy,
    ) {
    }

    /** @return array{createdAt:?string,updatedAt:?string,createdBy:?string,updatedBy:?string} */
    public function toArray(): array
    {
        return [
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
        ];
    }
}
