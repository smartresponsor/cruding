<?php

declare(strict_types=1);

namespace App\Cruding\Provider\Crud;

use App\Cruding\Dto\Crud\CrudOwnership;
use App\Cruding\Dto\Crud\CrudOwnershipResolutionContext;
use App\Cruding\ServiceInterface\Crud\CrudOwnershipProviderInterface;

final readonly class DefaultCrudOwnershipProvider implements CrudOwnershipProviderInterface
{
    private const GETTER_LIST = ['getVendor', 'getOwner', 'getUser', 'getCreatedByUser', 'getAuthor', 'getCreatedBy'];

    public function supports(CrudOwnershipResolutionContext $context): bool
    {
        foreach (self::GETTER_LIST as $getter) {
            if (method_exists($context->object, $getter)) {
                return true;
            }
        }

        return false;
    }

    public function resolve(CrudOwnershipResolutionContext $context): CrudOwnership
    {
        foreach (self::GETTER_LIST as $getter) {
            if (!method_exists($context->object, $getter)) {
                continue;
            }
            $owner = $context->object->{$getter}();
            $isOwner = null !== $context->actor && $this->matches($context->actor, $owner);

            return new CrudOwnership(true, null !== $context->actor, $isOwner, $context->isAdmin, $getter);
        }

        return new CrudOwnership(false, null !== $context->actor, false, $context->isAdmin, null);
    }

    private function matches(object $actor, mixed $owner): bool
    {
        if (is_object($owner)) {
            return $owner === $actor || $this->id($owner) === $this->id($actor);
        }

        $actorId = $this->id($actor);

        return is_scalar($owner) && null !== $actorId && (string) $owner === (string) $actorId;
    }

    private function id(object $object): string|int|null
    {
        if (!method_exists($object, 'getId')) {
            return null;
        }

        $id = $object->getId();

        return is_scalar($id) ? $id : null;
    }
}
