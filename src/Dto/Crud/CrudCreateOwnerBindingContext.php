<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Crud;

use Symfony\Component\HttpFoundation\Request;

final readonly class CrudCreateOwnerBindingContext
{
    public function __construct(
        public CrudContext $crudContext,
        public object $object,
        public Request $request,
        public object $actor,
    ) {
    }
}
