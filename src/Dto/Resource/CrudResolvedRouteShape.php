<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Resource;

/**
 * Internal normalized route grammar extracted from Symfony route/path data.
 */
final readonly class CrudResolvedRouteShape
{
    public function __construct(
        public string $resource,
        public string $operation,
        public ?string $viewPath = null,
        public ?string $ViewToken = null,
        public ?string $subjectField = null,
        public string|int|null $subjectValue = null,
        public ?string $itemField = null,
        public string|int|null $itemValue = null,
    ) {
    }
}
