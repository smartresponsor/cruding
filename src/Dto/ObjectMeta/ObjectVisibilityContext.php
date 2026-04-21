<?php

declare(strict_types=1);

namespace App\Cruding\Dto\ObjectMeta;

final readonly class ObjectVisibilityContext
{
    public function __construct(
        public bool $visible,
        public bool $published,
        public bool $archived,
        public bool $draft,
    ) {
    }

    /** @return array{visible:bool,published:bool,archived:bool,draft:bool} */
    public function toArray(): array
    {
        return [
            'visible' => $this->visible,
            'published' => $this->published,
            'archived' => $this->archived,
            'draft' => $this->draft,
        ];
    }
}
