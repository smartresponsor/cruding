<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudCreateOwnerBindingContext;
use App\Cruding\ServiceInterface\Crud\CrudCreateOwnerBinderInterface;

final readonly class DefaultCrudCreateOwnerBinder implements CrudCreateOwnerBinderInterface
{
    /** @var list<string> */
    private const SETTER_LIST = [
        'setVendor',
        'setOwner',
        'setUser',
        'setCreatedByUser',
        'setAuthor',
    ];

    public function supports(CrudCreateOwnerBindingContext $context): bool
    {
        foreach (self::SETTER_LIST as $setter) {
            if (method_exists($context->object, $setter)) {
                return true;
            }
        }

        return false;
    }

    public function bind(CrudCreateOwnerBindingContext $context): void
    {
        foreach (self::SETTER_LIST as $setter) {
            if (method_exists($context->object, $setter)) {
                $context->object->{$setter}($context->actor);

                return;
            }
        }
    }
}
