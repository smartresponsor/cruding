<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation\Create;

use App\Cruding\Dto\Crud\CrudContext;

final readonly class CrudCreateWorkItem
{
    public function __construct(
        public CrudContext $context,
        public object $object,
    ) {
    }
}
