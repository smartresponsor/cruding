<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudCreateOwnerBindingContext;
use App\Cruding\ServiceInterface\Crud\CrudCreateOwnerBinderInterface;

final readonly class CrudCreateOwnerBinderChain
{
    /** @param iterable<CrudCreateOwnerBinderInterface> $binderList */
    public function __construct(private iterable $binderList)
    {
    }

    public function bind(CrudCreateOwnerBindingContext $context): bool
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
