<?php

declare(strict_types=1);

namespace App\Cruding\DTO;

use Symfony\Component\HttpFoundation\Request;

/**
 * Carries create owner binding context data across Cruding processing boundaries.
 */
final readonly class CrudCreateOwnerBindingContextDTO
{
    public function __construct(
        public CrudContextDTO $crudContext,
        public object $object,
        public Request $request,
        public object $actor,
    ) {
    }
}
