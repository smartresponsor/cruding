<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class CrudObjectFinder implements CrudObjectFinderInterface
{
    private const DEFAULT_PAGE_SIZE = 25;
    private const MAX_PAGE_SIZE = 500;
    private const OWNER_ASSOCIATION_FIELDS = ['vendor', 'owner', 'user', 'createdBy', 'createdByUser', 'author'];
    private const OWNER_SCALAR_FIELDS = ['ownerUserId', 'userId', 'createdByUserId', 'authorUserId'];

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private RequestStack $requestStack,
        private Security $security,
    ) {
    }

    public function findOne(CrudContext $context): ?object
    {
        $manager = $this->managerRegistry->getManagerForClass($context->entityClass);
        if (null === $manager) {
            return null;
        }

        $metadata = $manager->getClassMetadata($context->entityClass);
        if (null === $context->identifierValue) {
            return 'page' === $context->operation
                ? $this->findActorOwned($context->entityClass, $metadata)
                : null;
        }

        $identifierField = $this->resolveIdentifierField($metadata, $context->identifierField);
        if (null === $identifierField) {
            return null;
        }

        $object = $this->managerRegistry->getRepository($context->entityClass)->findOneBy([
            $identifierField => $context->identifierValue,
        ]);

        return $object;
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

            return $repository->findBy([], null, $limit, $offset);
        } finally {
            $this->requestStack->getCurrentRequest()?->attributes->set('_crud_object_find_all_ms', number_format((hrtime(true) - $startedAt) / 1_000_000, 2, '.', ''));
        }
    }

    private function findActorOwned(string $entityClass, object $metadata): ?object
    {
        $user = $this->security->getUser();
        $request = $this->requestStack->getCurrentRequest();
        if (null === $user) {
            $request?->attributes->set('_crud_implicit_object_reason', 'authentication_required');
            $request?->attributes->set('_crud_implicit_actor_class', null);
            $request?->attributes->set('_crud_implicit_actor_id', null);

            return null;
        }

        $request?->attributes->set('_crud_implicit_actor_class', $user::class);
        $request?->attributes->set('_crud_implicit_actor_id', method_exists($user, 'getId') && is_scalar($user->getId()) ? $user->getId() : null);

        $repository = $this->managerRegistry->getRepository($entityClass);
        $request?->attributes->set('_crud_implicit_repository_class', $repository::class);
        $userId = method_exists($user, 'getId') ? $user->getId() : null;
        if (is_int($userId) && method_exists($repository, 'findOneForUserId')) {
            $object = $repository->findOneForUserId($userId);
            if (is_object($object)) {
                $request?->attributes->set('_crud_implicit_object_reason', 'repository_current_user_match');

                return $object;
            }

            $request?->attributes->set('_crud_implicit_object_reason', 'repository_current_user_not_found');
        }

        foreach (self::OWNER_ASSOCIATION_FIELDS as $field) {
            if ($metadata->hasAssociation($field)) {
                return $repository->findOneBy([$field => $user]);
            }
        }

        if (!method_exists($user, 'getId')) {
            return null;
        }

        $userId = $user->getId();
        if (!is_scalar($userId)) {
            return null;
        }

        foreach (self::OWNER_SCALAR_FIELDS as $field) {
            if ($metadata->hasField($field)) {
                return $repository->findOneBy([$field => $userId]);
            }
        }

        return null;
    }

    /** @return array{0:int,1:int} */
    private function pagination(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $limit = max(1, min(self::MAX_PAGE_SIZE, (int) $request?->query->get('limit', self::DEFAULT_PAGE_SIZE)));
        $page = max(1, (int) $request?->query->get('page', 1));

        return [$limit, ($page - 1) * $limit];
    }

    private function resolveIdentifierField(object $metadata, string $logicalField): ?string
    {
        if (method_exists($metadata, 'hasField') && $metadata->hasField($logicalField)) {
            return $logicalField;
        }

        if ('slug' !== $logicalField || !method_exists($metadata, 'hasField')) {
            return null;
        }

        foreach (['objectSlug', 'objectIdentity.objectSlug'] as $candidate) {
            if ($metadata->hasField($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
