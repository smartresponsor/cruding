<?php

declare(strict_types=1);

namespace App\Cruding\Provider;

use App\Cruding\DTO\CrudOwnershipDTO;
use App\Cruding\DTO\CrudOwnershipResolutionContextDTO;
use App\Cruding\ServiceInterface\CrudOwnershipProviderInterface;

/**
 * Provides default crud ownership provider data to Cruding consumers.
 */
final readonly class DefaultCrudOwnershipProvider implements CrudOwnershipProviderInterface
{
    private const GETTER_LIST = ['getVendor', 'getOwner', 'getUser', 'getCreatedByUser', 'getAuthor', 'getCreatedBy'];

    /**      * Indicates whether this implementation supports the supplied context.      */
    public function supports(CrudOwnershipResolutionContextDTO $context): bool
    {
        foreach (self::GETTER_LIST as $getter) {
            if (method_exists($context->object, $getter)) {
                return true;
            }
        }

        return false;
    }

    /**      * Executes the resolve operation.      */
    public function resolve(CrudOwnershipResolutionContextDTO $context): CrudOwnershipDTO
    {
        foreach (self::GETTER_LIST as $getter) {
            if (!method_exists($context->object, $getter)) {
                continue;
            }
            $owner = $context->object->{$getter}();
            $isOwner = null !== $context->actor && $this->matches($context->actor, $owner);

            return new CrudOwnershipDTO(true, null !== $context->actor, $isOwner, $context->isAdmin, $getter);
        }

        return new CrudOwnershipDTO(false, null !== $context->actor, false, $context->isAdmin, null);
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
