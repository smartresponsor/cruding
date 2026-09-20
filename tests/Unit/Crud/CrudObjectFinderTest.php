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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\InMemoryUser;

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

    public function testFindOneReturnsNullForEmptyEntityClass(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::never())->method('getManagerForClass');
        $registry->expects(self::never())->method('getRepository');

        $context = new CrudContextDTO('public', 'show', 'document', '', 'id', 1, null);

        self::assertNull($this->finder($registry)->findOne($context));
    }

    public function testFindOneReturnsNullWhenNoManagerOwnsEntityClass(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn(null);
        $context = new CrudContextDTO('public', 'show', 'document', 'App\\Tests\\Fixture\\Entity\\ProductEntity', 'id', 1, null);

        self::assertNull($this->finder($registry)->findOne($context));
    }

    public function testFindOneUsesObjectSlugFallback(): void
    {
        $expected = new \stdClass();
        $metadata = $this->createStub(ClassMetadata::class);
        $metadata->method('hasField')->willReturnCallback(static fn (string $field): bool => 'objectSlug' === $field);

        $manager = $this->createStub(ObjectManager::class);
        $manager->method('getClassMetadata')->willReturn($metadata);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())->method('findOneBy')->with(['objectSlug' => 'alpha'])->willReturn($expected);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);
        $registry->method('getRepository')->willReturn($repository);
        $context = new CrudContextDTO('public', 'show', 'product', 'App\\Tests\\Fixture\\Entity\\ProductEntity', 'slug', 'alpha', null);

        self::assertSame($expected, $this->finder($registry)->findOne($context));
    }

    public function testFindAllAppliesBoundedPaginationAndRecordsTiming(): void
    {
        $request = Request::create('/product?limit=999&page=3');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())->method('findBy')->with([], null, 500, 1000)->willReturn([]);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getRepository')->willReturn($repository);
        $finder = new CrudObjectFinder($registry, $requestStack, $this->createStub(Security::class));
        $context = new CrudContextDTO('public', 'index', 'product', 'App\\Tests\\Fixture\\Entity\\ProductEntity', 'id', null, null);

        self::assertSame([], $finder->findAll($context));
        $timing = $request->attributes->get('_crud_object_find_all_ms');
        self::assertIsString($timing);
        self::assertMatchesRegularExpression('/^\\d+\\.\\d{2}$/', $timing);
    }

    public function testPageLookupWithoutAuthenticatedActorRecordsReason(): void
    {
        $request = Request::create('/product/page');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $metadata = $this->createStub(ClassMetadata::class);
        $manager = $this->createStub(ObjectManager::class);
        $manager->method('getClassMetadata')->willReturn($metadata);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);
        $finder = new CrudObjectFinder($registry, $requestStack, $security);
        $context = new CrudContextDTO('public', 'page', 'product', 'App\\Tests\\Fixture\\Entity\\ProductEntity', 'id', null, null);

        self::assertNull($finder->findOne($context));
        self::assertSame('authentication_required', $request->attributes->get('_crud_implicit_object_reason'));
        self::assertNull($request->attributes->get('_crud_implicit_actor_class'));
        self::assertNull($request->attributes->get('_crud_implicit_actor_id'));
    }

    public function testPageLookupFallsBackToOwnerAssociation(): void
    {
        $request = Request::create('/product/page');
        $requestStack = new RequestStack();
        $requestStack->push($request);
        $user = new InMemoryUser('actor@example.test', null);
        $expected = new \stdClass();

        $metadata = $this->createStub(ClassMetadata::class);
        $metadata->method('hasAssociation')->willReturnCallback(static fn (string $field): bool => 'owner' === $field);
        $manager = $this->createStub(ObjectManager::class);
        $manager->method('getClassMetadata')->willReturn($metadata);
        $repository = $this->createMock(ObjectRepository::class);
        $repository->expects(self::once())->method('findOneBy')->with(['owner' => $user])->willReturn($expected);
        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($manager);
        $registry->method('getRepository')->willReturn($repository);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);
        $finder = new CrudObjectFinder($registry, $requestStack, $security);
        $context = new CrudContextDTO('public', 'page', 'product', 'App\\Tests\\Fixture\\Entity\\ProductEntity', 'id', null, null);

        self::assertSame($expected, $finder->findOne($context));
        self::assertSame(InMemoryUser::class, $request->attributes->get('_crud_implicit_actor_class'));
        self::assertSame($repository::class, $request->attributes->get('_crud_implicit_repository_class'));
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
