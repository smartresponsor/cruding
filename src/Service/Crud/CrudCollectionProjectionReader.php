<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;

final class CrudCollectionProjectionReader
{
    private const DEFAULT_PAGE_SIZE = 25;
    private const MAX_PAGE_SIZE = 500;

    /** @var array<class-string, array{id: string, select: list<string>}> */
    private array $projectionPlanByEntityClass = [];

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function read(CrudContext $context): ?array
    {
        $manager = $this->managerRegistry->getManagerForClass($context->entityClass);
        if (!$manager instanceof EntityManagerInterface) {
            return null;
        }

        $projectionPlan = $this->projectionPlan($manager, $context->entityClass);
        if (null === $projectionPlan) {
            return null;
        }

        [$limit, $offset] = $this->pagination();
        $queryBuilder = $manager->createQueryBuilder()
            ->select($projectionPlan['select'])
            ->from($context->entityClass, 'entity')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $startedAt = hrtime(true);
        $rows = $queryBuilder->getQuery()->getArrayResult();
        $this->requestStack->getCurrentRequest()?->attributes->set(
            '_crud_collection_projection_ms',
            number_format((hrtime(true) - $startedAt) / 1_000_000, 2, '.', ''),
        );

        return array_map(
            static fn (array $row): array => [
                'id' => $row['id'],
                'title' => $row['title'] ?? $row['code'] ?? (string) $row['id'],
                'code' => $row['code'] ?? (string) $row['id'],
                'owner' => 'cruding',
                'status' => self::status($row),
                'locale' => $row['locale'] ?? 'en',
            ],
            $rows,
        );
    }

    /**
     * @param class-string $entityClass
     *
     * @return array{id: string, select: list<string>}|null
     */
    private function projectionPlan(EntityManagerInterface $manager, string $entityClass): ?array
    {
        if (isset($this->projectionPlanByEntityClass[$entityClass])) {
            return $this->projectionPlanByEntityClass[$entityClass];
        }

        $fieldMap = $this->fieldMap($manager->getClassMetadata($entityClass)->getFieldNames());
        if (null === $fieldMap['id']) {
            return null;
        }

        $select = [];
        foreach ($fieldMap as $alias => $field) {
            if (null !== $field) {
                $select[] = sprintf('entity.%s AS %s', $field, $alias);
            }
        }

        return $this->projectionPlanByEntityClass[$entityClass] = [
            'id' => $fieldMap['id'],
            'select' => $select,
        ];
    }

    /**
     * @param list<string> $fields
     *
     * @return array{id: string|null, title: string|null, code: string|null, status: string|null, enabled: string|null, locale: string|null}
     */
    private function fieldMap(array $fields): array
    {
        return [
            'id' => $this->firstField($fields, ['id', 'uuid', 'ulid']),
            'title' => $this->firstField($fields, ['title', 'nameEntity', 'name', 'label']),
            'code' => $this->firstField($fields, ['code', 'sku', 'slug']),
            'status' => $this->firstField($fields, ['status', 'workflowState', 'state']),
            'enabled' => $this->firstField($fields, ['enabled', 'active', 'published']),
            'locale' => $this->firstField($fields, ['locale', 'contentLocale']),
        ];
    }

    /** @param list<string> $fields */
    private function firstField(array $fields, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (in_array($candidate, $fields, true)) {
                return $candidate;
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

    /** @param array<string, mixed> $row */
    private static function status(array $row): mixed
    {
        if (array_key_exists('status', $row) && null !== $row['status']) {
            return $row['status'];
        }

        if (array_key_exists('enabled', $row)) {
            return (bool) $row['enabled'] ? 'active' : 'inactive';
        }

        return 'loaded';
    }
}
