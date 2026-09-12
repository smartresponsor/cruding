<?php

declare(strict_types=1);

namespace App\Cruding\Service;

use App\Cruding\DTO\CrudCreateOwnerBindingContextDTO;
use App\Cruding\ServiceInterface\CrudCreateOwnerBinderInterface;

/**
 * Provides the default crud create owner binder responsibility within the Cruding component.
 */
final readonly class CrudDefaultCreateOwnerBinder implements CrudCreateOwnerBinderInterface
{
    /** @var list<string> */
    private const SETTER_LIST = [
        'setVendor',
        'setOwner',
        'setUser',
        'setCreatedByUser',
        'setAuthor',
    ];

    /**      * Indicates whether this implementation supports the supplied context.      */
    public function supports(CrudCreateOwnerBindingContextDTO $context): bool
    {
        foreach (self::SETTER_LIST as $setter) {
            if (method_exists($context->object, $setter)) {
                return true;
            }
        }

        return false;
    }

    /**      * Executes the bind operation.      */
    public function bind(CrudCreateOwnerBindingContextDTO $context): void
    {
        foreach (self::SETTER_LIST as $setter) {
            if (method_exists($context->object, $setter)) {
                $context->object->{$setter}($context->actor);

                return;
            }
        }
    }
}
