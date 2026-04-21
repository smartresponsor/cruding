<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudOwnership;
use App\Cruding\ServiceInterface\Crud\CrudOwnershipResolverInterface;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class CrudOwnershipResolver implements CrudOwnershipResolverInterface
{
    private const OWNER_GETTERS = [
        'getCreatedBy',
        'getOwner',
        'getUser',
        'getCreatedByUser',
        'getAuthor',
    ];

    public function __construct(private Security $security)
    {
    }

    public function resolve(?object $object): CrudOwnership
    {
        $user = $this->security->getUser();
        $isAdmin = $this->security->isGranted('ROLE_ADMIN') || $this->security->isGranted('ROLE_SUPER_ADMIN');

        if (null === $object) {
            return new CrudOwnership(false, null !== $user, false, $isAdmin, null);
        }

        foreach (self::OWNER_GETTERS as $getter) {
            if (!method_exists($object, $getter)) {
                continue;
            }

            $owner = $object->{$getter}();
            $isOwner = null !== $user && $this->matchesOwner($user, $owner);

            return new CrudOwnership(true, null !== $user, $isOwner, $isAdmin, $getter);
        }

        return new CrudOwnership(false, null !== $user, false, $isAdmin, null);
    }

    private function matchesOwner(object $user, mixed $owner): bool
    {
        if (is_object($owner)) {
            return $owner === $user || $this->readId($owner) === $this->readId($user);
        }

        if (is_scalar($owner)) {
            return $owner === $this->readId($user);
        }

        return false;
    }

    private function readId(object $object): string|int|null
    {
        if (!method_exists($object, 'getId')) {
            return null;
        }

        $id = $object->getId();

        return is_scalar($id) ? $id : null;
    }
}
