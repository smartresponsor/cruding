<?php

declare(strict_types=1);

namespace App\Cruding\DTO;

use Symfony\Component\HttpFoundation\Request;

/**
 * Carries backend bulk mutation context across Cruding execution boundaries.
 */
final readonly class CrudBulkMutationContextDTO
{
    public function __construct(
        public CrudContextDTO $crudContext,
        public Request $request,
        public string $action,
        public string $dataScope,
    ) {
    }
}
