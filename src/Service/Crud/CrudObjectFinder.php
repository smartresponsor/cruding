<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Crud\CrudOwnershipResolverInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class CrudObjectFinder implements CrudObjectFinderInterface
{
    private const OWNER_FIELDS = ['vendor', 'owner', 'user', 'createdBy', 'createdByUser', 'author'];

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private RequestStack $requestStack,
        private Security $security,
        private CrudOwnershipResolverInterface $ownershipResolver,
    ) {
    }

    public function findOne(CrudContext $context): ?object
    {
        if (null === $context->identifierValue) {
            return null;
        }

        $object = $this->managerRegistry->getRepository($context->entityClass)->findOneBy([
            $context->identifierField => $context->identifierValue,
        ]);
        if (null === $object || !$this->isMyScoped()) {
            return $object;
        }

        return $this->ownershipResolver->resolve($object)->isOwner ? $object : null;
    }

    /**
     * @return list<object>
     */
    public function findAll(CrudContext $context): array
    {
        $repository = $this->managerRegistry->getRepository($context->entityClass);
        if (!$this->isMyScoped()) {
            return $repository->findAll();
        }

        $user = $this->security->getUser();
        if (null === $user) {
            return [];
        }

        $manager = $this->managerRegistry->getManagerForClass($context->entityClass);
        if (null === $manager) {
            return [];
        }
        $metadata = $manager->getClassMetadata($context->entityClass);
        foreach (self::OWNER_FIELDS as $field) {
            if ($metadata->hasAssociation($field)) {
                return $repository->findBy([$field => $user]);
            }
            if ($metadata->hasField($field) && method_exists($user, 'getId')) {
                $userId = $user->getId();
                if (is_scalar($userId)) {
                    return $repository->findBy([$field => $userId]);
                }
            }
        }

        return [];
    }

    private function isMyScoped(): bool
    {
        return 'my' === $this->requestStack->getCurrentRequest()?->attributes->get('_crud_actor_scope');
    }
}
