<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\Service\CrudObjectFinder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

final class CrudObjectFinderTest extends TestCase
{
    public function testFindAllPropagatesRepositoryFailure(): void
    {
        $failure = new \RuntimeException('db unavailable');
        $repository = $this->createStub(ObjectRepository::class);
        $repository->method('findBy')->willThrowException($failure);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repository);

        $finder = $this->finder($registry);
        $context = new CrudContextDTO('public', 'index', 'document', 'App\\Tests\\Fixture\\Entity\\DocumentEntity', 'id', null, null);

        $this->expectExceptionObject($failure);

        $finder->findAll($context);
    }

    public function testFindOnePropagatesRepositoryFailure(): void
    {
        $failure = new \RuntimeException('db unavailable');
        $repository = $this->createStub(ObjectRepository::class);
        $repository->method('findOneBy')->willThrowException($failure);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('hasField')->with('id')->willReturn(true);
        $manager = $this->createStub(ObjectManager::class);
        $manager->method('getClassMetadata')->willReturn($metadata);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);
        $registry->method('getRepository')->willReturn($repository);

        $finder = $this->finder($registry);
        $context = new CrudContextDTO('public', 'show', 'document', 'App\\Tests\\Fixture\\Entity\\DocumentEntity', 'id', 1, null);

        $this->expectExceptionObject($failure);

        $finder->findOne($context);
    }

    public function testFindOneWithoutIdentifierDoesNotAccessRegistry(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::never())->method('getRepository');

        $finder = $this->finder($registry);
        $context = new CrudContextDTO('public', 'show', 'document', 'App\\Tests\\Fixture\\Entity\\DocumentEntity', 'id', null, null);

        self::assertNull($finder->findOne($context));
    }

    private function finder(ManagerRegistry $registry): CrudObjectFinder
    {
        return new CrudObjectFinder(
            $registry,
            new RequestStack(),
            $this->createStub(Security::class),
        );
    }
}
