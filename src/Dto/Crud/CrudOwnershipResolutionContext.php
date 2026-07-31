<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Crud;

final readonly class CrudOwnershipResolutionContext
{
    public function __construct(
        public object $object,
        public ?object $actor,
        public bool $isAdmin,
    ) {
    }
}
