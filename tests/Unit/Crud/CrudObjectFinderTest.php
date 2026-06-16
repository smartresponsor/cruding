<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Service\Crud\CrudObjectFinder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;

final class CrudObjectFinderTest extends TestCase
{
    public function testFindAllPropagatesRepositoryFailure(): void
    {
        $failure = new \RuntimeException('db unavailable');
        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('findAll')->willThrowException($failure);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repository);

        $finder = new CrudObjectFinder($registry);
        $context = new CrudContext('public', 'index', 'document', 'App\\Tests\\Fixture\\Entity\\DocumentEntity', 'id', null, null);

        $this->expectExceptionObject($failure);

        $finder->findAll($context);
    }

    public function testFindOnePropagatesRepositoryFailure(): void
    {
        $failure = new \RuntimeException('db unavailable');
        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('findOneBy')->willThrowException($failure);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repository);

        $finder = new CrudObjectFinder($registry);
        $context = new CrudContext('public', 'show', 'document', 'App\\Tests\\Fixture\\Entity\\DocumentEntity', 'id', 1, null);

        $this->expectExceptionObject($failure);

        $finder->findOne($context);
    }

    public function testFindOneWithoutIdentifierDoesNotAccessRegistry(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::never())->method('getRepository');

        $finder = new CrudObjectFinder($registry);
        $context = new CrudContext('public', 'show', 'document', 'App\\Tests\\Fixture\\Entity\\DocumentEntity', 'id', null, null);

        self::assertNull($finder->findOne($context));
    }
}
