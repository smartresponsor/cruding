<?php

declare(strict_types=1);

namespace App\Cruding\Dto;

use Symfony\Component\HttpFoundation\Request;

/**
 * Carries mutation lifecycle context data across Cruding processing boundaries.
 */
final readonly class CrudMutationLifecycleContextDTO
{
    public function __construct(
        public CrudContextDTO $crudContext,
        public object $object,
        public Request $request,
        public string $operation,
    ) {
    }
}
