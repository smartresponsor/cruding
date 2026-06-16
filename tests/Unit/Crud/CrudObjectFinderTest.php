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
    public function testFindAllReturnsEmptyArrayWhenRepositoryFails(): void
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('findAll')->willThrowException(new \RuntimeException('db unavailable'));

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repository);

        $finder = new CrudObjectFinder($registry);
        $context = new CrudContext('public', 'index', 'vendor', 'App\\Vendoring\\Entity\\Vendor\\VendorEntity', 'id', null, null, 'vendor');

        self::assertSame([], $finder->findAll($context));
    }

    public function testFindOneReturnsNullWhenRepositoryFails(): void
    {
        $repository = $this->createMock(ObjectRepository::class);
        $repository->method('findOneBy')->willThrowException(new \RuntimeException('db unavailable'));

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repository);

        $finder = new CrudObjectFinder($registry);
        $context = new CrudContext('public', 'show', 'vendor', 'App\\Vendoring\\Entity\\Vendor\\VendorEntity', 'id', 1, null, 'vendor');

        self::assertNull($finder->findOne($context));
    }
}
