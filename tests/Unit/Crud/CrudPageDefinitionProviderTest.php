<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\DTO\CrudAccessContextDTO;
use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\CrudOwnershipDTO;
use App\Cruding\Provider\CrudPageDefinitionProvider;
use App\Cruding\Service\CrudCollectionProjectionReader;
use App\Cruding\ServiceInterface\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\CrudRouteNameResolverInterface;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;

final class CrudPageDefinitionProviderTest extends TestCase
{
    public function testProvideIndexBuildsviewReadyPageDefinition(): void
    {
        $context = new CrudContextDTO('public', 'index', 'product', 'App\\Entity\\Product', 'slug', null, 'App\\Form\\ProductType');
        $access = new CrudAccessContextDTO(
            $context,
            true,
            true,
            new CrudOwnershipDTO(false, true, false, false, null),
            true,
            true,
            true,
        );
        $objects = [new class {
            public function __toString(): string
            {
                return 'demo';
            }
        }];

        $objectFinder = new class($objects) implements CrudObjectFinderInterface {
            /** @param list<object> $objects */
            public function __construct(private array $objects)
            {
            }

            public function findOne(CrudContextDTO $context): ?object
            {
                return null;
            }

            public function findAll(CrudContextDTO $context): array
            {
                return $this->objects;
            }
        };

        $accessBuilder = new class($access) implements CrudAccessContextBuilderInterface {
            public function __construct(private CrudAccessContextDTO $access)
            {
            }

            public function build(CrudContextDTO $context, ?object $object = null): CrudAccessContextDTO
            {
                return $this->access;
            }
        };

        $routeResolver = new class implements CrudRouteNameResolverInterface {
            public function resolveIndex(CrudContextDTO $context): string
            {
                return 'cruding_tokenized_catch_all';
            }

            public function resolveNew(CrudContextDTO $context): string
            {
                return 'cruding_tokenized_catch_all';
            }

            public function resolveShow(CrudContextDTO $context, ?string $identifierField = null): string
            {
                return 'cruding_tokenized_catch_all';
            }

            public function resolveEdit(CrudContextDTO $context, ?string $identifierField = null): string
            {
                return 'cruding_tokenized_catch_all';
            }

            public function resolveDelete(CrudContextDTO $context, ?string $identifierField = null): string
            {
                return 'cruding_tokenized_catch_all';
            }

            public function parameters(CrudContextDTO $context, string|int|null $identifierValue = null, ?string $identifierField = null, ?string $operation = null): array
            {
                return ['crudPath' => $context->resourcePath];
            }
        };

        $provider = new CrudPageDefinitionProvider($objectFinder, $this->projectionReader(), $accessBuilder, $routeResolver);
        $page = $provider->provideIndex($context);

        self::assertSame('product index', $page->title);
        self::assertSame('index', $page->template);
        self::assertSame($objects, $page->objects);
        self::assertCount(1, $page->actions);
        self::assertSame('new', $page->actions[0]->nameEntity);
        self::assertSame('cruding_tokenized_catch_all', $page->actions[0]->routeName);
        self::assertSame('product', $page->meta['resourcePath']);
    }

    public function testProvideIndexOmitsCreateActionWhenFormTypeIsMissing(): void
    {
        $context = new CrudContextDTO('public', 'index', 'product', 'App\\Entity\\Product', 'slug', null, null);
        $access = new CrudAccessContextDTO(
            $context,
            true,
            true,
            new CrudOwnershipDTO(false, true, false, false, null),
            true,
            true,
            true,
        );

        $objectFinder = new class implements CrudObjectFinderInterface {
            public function findOne(CrudContextDTO $context): ?object
            {
                return null;
            }

            public function findAll(CrudContextDTO $context): array
            {
                return [];
            }
        };

        $accessBuilder = new class($access) implements CrudAccessContextBuilderInterface {
            public function __construct(private CrudAccessContextDTO $access)
            {
            }

            public function build(CrudContextDTO $context, ?object $object = null): CrudAccessContextDTO
            {
                return $this->access;
            }
        };

        $routeResolver = new class implements CrudRouteNameResolverInterface {
            public function resolveIndex(CrudContextDTO $context): string
            {
                return 'cruding_tokenized_catch_all';
            }

            public function resolveNew(CrudContextDTO $context): string
            {
                return 'cruding_tokenized_catch_all';
            }

            public function resolveShow(CrudContextDTO $context, ?string $identifierField = null): string
            {
                return 'cruding_tokenized_catch_all';
            }

            public function resolveEdit(CrudContextDTO $context, ?string $identifierField = null): string
            {
                return 'cruding_tokenized_catch_all';
            }

            public function resolveDelete(CrudContextDTO $context, ?string $identifierField = null): string
            {
                return 'cruding_tokenized_catch_all';
            }

            public function parameters(CrudContextDTO $context, string|int|null $identifierValue = null, ?string $identifierField = null, ?string $operation = null): array
            {
                return ['crudPath' => $context->resourcePath];
            }
        };

        $provider = new CrudPageDefinitionProvider($objectFinder, $this->projectionReader(), $accessBuilder, $routeResolver);
        $page = $provider->provideIndex($context);

        self::assertSame([], $page->actions);
    }

    public function testProvideShowBuildsShellReadyPageDefinition(): void
    {
        $context = new CrudContextDTO('public', 'show', 'product', 'App\\Tests\\Fixture\\Entity\\ProductEntity', 'id', 13, 'App\\Tests\\Fixture\\Form\\ProductEntityType');
        $access = new CrudAccessContextDTO(
            $context,
            false,
            true,
            new CrudOwnershipDTO(false, true, false, false, null),
            true,
            true,
            false,
        );
        $object = new class {
            public function getId(): int
            {
                return 13;
            }
        };

        $objectFinder = new class($object) implements CrudObjectFinderInterface {
            public function __construct(private object $object)
            {
            }

            public function findOne(CrudContextDTO $context): object
            {
                return $this->object;
            }

            public function findAll(CrudContextDTO $context): array
            {
                return [$this->object];
            }
        };

        $accessBuilder = new class($access) implements CrudAccessContextBuilderInterface {
            public function __construct(private CrudAccessContextDTO $access)
            {
            }

            public function build(CrudContextDTO $context, ?object $object = null): CrudAccessContextDTO
            {
                return $this->access;
            }
        };

        $routeResolver = new class implements CrudRouteNameResolverInterface {
            public function resolveIndex(CrudContextDTO $context): string
            {
                return 'cruding_tokenized_catch_all';
            }

            public function resolveNew(CrudContextDTO $context): string
            {
                return 'cruding_tokenized_catch_all';
            }

            public function resolveShow(CrudContextDTO $context, ?string $identifierField = null): string
            {
                return 'cruding_show_id';
            }

            public function resolveEdit(CrudContextDTO $context, ?string $identifierField = null): string
            {
                return 'cruding_edit_id';
            }

            public function resolveDelete(CrudContextDTO $context, ?string $identifierField = null): string
            {
                return 'cruding_delete_id';
            }

            public function parameters(CrudContextDTO $context, string|int|null $identifierValue = null, ?string $identifierField = null, ?string $operation = null): array
            {
                $id = $identifierValue ?? $context->identifierValue;

                return [
                    'resourcePath' => $context->resourcePath,
                    'id' => is_int($id) || is_string($id) ? $id : '',
                ];
            }
        };

        $provider = new CrudPageDefinitionProvider($objectFinder, $this->projectionReader(), $accessBuilder, $routeResolver);
        $page = $provider->provideShow($context, $object);

        self::assertSame('product show', $page->title);
        self::assertSame('detail', $page->template);
        self::assertSame([$object], $page->objects);
        self::assertCount(2, $page->actions);
        self::assertSame('index', $page->actions[0]->nameEntity);
        self::assertSame('edit', $page->actions[1]->nameEntity);
        self::assertSame('product', $page->meta['resourcePath']);
        self::assertSame(13, $page->meta['identifierValue']);
    }

    private function projectionReader(): CrudCollectionProjectionReader
    {
        return new CrudCollectionProjectionReader(
            $this->createStub(ManagerRegistry::class),
            new RequestStack(),
        );
    }
}
