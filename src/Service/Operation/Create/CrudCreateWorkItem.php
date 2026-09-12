<?php

declare(strict_types=1);

namespace App\Cruding\Service\Operation\Create;

use App\Cruding\DTO\CrudContextDTO;

/**
 * Provides the create work item responsibility within the Cruding component.
 */
final readonly class CrudCreateWorkItem
{
    public function __construct(
        public CrudContextDTO $context,
        public object $object,
    ) {
    }
}
