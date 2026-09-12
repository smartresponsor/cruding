<?php

declare(strict_types=1);

namespace App\Cruding\Service;

use App\Cruding\DTO\CrudCreateOwnerBindingContextDTO;
use App\Cruding\ServiceInterface\CrudCreateOwnerBinderInterface;

/**
 * Provides the create owner binder chain responsibility within the Cruding component.
 */
final readonly class CrudCreateOwnerBinderChain
{
    /** @param iterable<CrudCreateOwnerBinderInterface> $binderList */
    public function __construct(private iterable $binderList)
    {
    }

    /**      * Executes the bind operation.      */
    public function bind(CrudCreateOwnerBindingContextDTO $context): bool
    {
        foreach ($this->binderList as $binder) {
            if ($binder->supports($context)) {
                $binder->bind($context);

                return true;
            }
        }

        return false;
    }
}
