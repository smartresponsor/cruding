<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\Exception\Crud\CrudResourceNotFoundException;
use App\Cruding\Service\Crud\CrudEntityClassResolver;
use App\Cruding\Service\Crud\CrudResourcePathParser;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;

final class CrudEntityClassResolverTest extends TestCase
{
    public function testResolveUsesDoctrineMetadataWithoutNeighborHardcode(): void
    {
        $classes = [
            'App\Tests\Fixture\Entity\ProductEntity',
            'App\Tests\Fixture\Entity\Product\ProductPriceEntity',
            'App\Tests\Fixture\Entity\Resource\ResourceCategoryEntity',
            'App\Tests\Fixture\Entity\Record\RecordEntity',
        ];

        $resolver = new CrudEntityClassResolver($this->buildRegistry($classes), new CrudResourcePathParser());

        self::assertSame($classes[0], $resolver->resolve('product'));
        self::assertSame($classes[1], $resolver->resolve('product/price'));
        self::assertSame($classes[2], $resolver->resolve('resource/category'));
        self::assertSame($classes[3], $resolver->resolve('record'));
        self::assertNull($resolver->tryResolve('unknown-resource'));
        self::expectException(CrudResourceNotFoundException::class);
        $resolver->resolve('unknown-resource');
    }

    public function testExplicitAliasMapOverridesMetadataDiscovery(): void
    {
        $resolver = new CrudEntityClassResolver(
            $this->buildRegistry(['App\Tests\Fixture\Entity\ProductEntity']),
            new CrudResourcePathParser(),
            ['resource/item' => 'App\Tests\Fixture\Entity\Resource\ResourceCategoryEntity'],
        );

        self::assertSame('App\Tests\Fixture\Entity\Resource\ResourceCategoryEntity', $resolver->resolve('resource_item'));
    }

    public function testTryResolveReusesMetadataCandidateMap(): void
    {
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects(self::once())
            ->method('getAllMetadata')
            ->willReturn([
                new class('App\Tests\Fixture\Entity\ProductEntity') {
                    public function __construct(private string $name)
                    {
                    }

                    public function getName(): string
                    {
                        return $this->name;
                    }
                },
            ]);

        $manager = $this->createStub(ObjectManager::class);
        $manager->method('getMetadataFactory')->willReturn($metadataFactory);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagers')->willReturn([$manager]);

        $resolver = new CrudEntityClassResolver($registry, new CrudResourcePathParser());

        self::assertSame('App\Tests\Fixture\Entity\ProductEntity', $resolver->tryResolve('product'));
        self::assertNull($resolver->tryResolve('missing'));
    }

    /**
     * @param list<class-string> $classes
     */
    private function buildRegistry(array $classes): ManagerRegistry
    {
        $metadataFactory = $this->createStub(ClassMetadataFactory::class);
        $metadataFactory->method('getAllMetadata')->willReturn(array_map(
            static fn (string $class): object => new class($class) {
                public function __construct(private string $name)
                {
                }

                public function getName(): string
                {
                    return $this->name;
                }
            },
            $classes
        ));

        $manager = $this->createStub(ObjectManager::class);
        $manager->method('getMetadataFactory')->willReturn($metadataFactory);

        $registry = $this->createStub(ManagerRegistry::class);
        $registry->method('getManagers')->willReturn([$manager]);

        return $registry;
    }
}
