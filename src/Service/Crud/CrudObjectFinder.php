<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use Doctrine\Persistence\ManagerRegistry;

final readonly class CrudObjectFinder implements CrudObjectFinderInterface
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    public function findOne(CrudContext $context): ?object
    {
        if (null === $context->identifierValue) {
            return null;
        }

        try {
            $repository = $this->managerRegistry->getRepository($context->entityClass);
            $object = $repository->findOneBy([$context->identifierField => $context->identifierValue]);
        } catch (\Throwable) {
            return null;
        }

        return $object;
    }

    /**
     * @return list<object>
     */
    public function findAll(CrudContext $context): array
    {
        try {
            return $this->managerRegistry->getRepository($context->entityClass)->findAll();
        } catch (\Throwable) {
            return [];
        }
    }
}
