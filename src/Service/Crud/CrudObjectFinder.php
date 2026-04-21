<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        $repository = $this->managerRegistry->getRepository($context->entityClass);
        $object = $repository->findOneBy([$context->identifierField => $context->identifierValue]);

        if (null === $object) {
            throw new NotFoundHttpException(sprintf('Object for resource "%s" was not found by %s "%s".', $context->resourcePath, $context->identifierField, (string) $context->identifierValue));
        }

        return $object;
    }

    /**
     * @return list<object>
     */
    public function findAll(CrudContext $context): array
    {
        return $this->managerRegistry->getRepository($context->entityClass)->findAll();
    }
}
