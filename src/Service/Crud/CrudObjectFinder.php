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
    private const DEFAULT_PAGE_SIZE = 25;
    private const MAX_PAGE_SIZE = 500;
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
        $startedAt = hrtime(true);
        try {
            $repository = $this->managerRegistry->getRepository($context->entityClass);
            [$limit, $offset] = $this->pagination();
            if (!$this->isMyScoped()) {
                return $repository->findBy([], null, $limit, $offset);
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
                    return $repository->findBy([$field => $user], null, $limit, $offset);
                }
                if ($metadata->hasField($field) && method_exists($user, 'getId')) {
                    $userId = $user->getId();
                    if (is_scalar($userId)) {
                        return $repository->findBy([$field => $userId], null, $limit, $offset);
                    }
                }
            }

            return [];
        } finally {
            $this->requestStack->getCurrentRequest()?->attributes->set('_crud_object_find_all_ms', number_format((hrtime(true) - $startedAt) / 1_000_000, 2, '.', ''));
        }
    }

    /** @return array{0:int,1:int} */
    private function pagination(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $limit = max(1, min(self::MAX_PAGE_SIZE, (int) $request?->query->get('limit', self::DEFAULT_PAGE_SIZE)));
        $page = max(1, (int) $request?->query->get('page', 1));

        return [$limit, ($page - 1) * $limit];
    }

    private function isMyScoped(): bool
    {
        return 'my' === $this->requestStack->getCurrentRequest()?->attributes->get('_crud_actor_scope');
    }
}
