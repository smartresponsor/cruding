<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Collectioning\DTO\CollectionPageDTO;
use App\Collectioning\DTO\CollectionResultDTO;
use App\Collectioning\ServiceInterface\CollectionRequestReaderInterface;
use App\Cruding\DTO\CrudAccessContextDTO;
use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\CrudOwnershipDTO;
use App\Cruding\Provider\CrudPageDefinitionProvider;
use App\Cruding\ServiceInterface\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\CrudRouteNameResolverInterface;
use PHPUnit\Framework\TestCase;

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

        $provider = new CrudPageDefinitionProvider($objectFinder, $this->collectionReader($objects), $accessBuilder, $routeResolver);
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

        $provider = new CrudPageDefinitionProvider($objectFinder, $this->collectionReader(), $accessBuilder, $routeResolver);
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

        $provider = new CrudPageDefinitionProvider($objectFinder, $this->collectionReader(), $accessBuilder, $routeResolver);
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

    public function testProvidePageWithoutObjectBuildsCollectionPage(): void
    {
        $context = new CrudContextDTO('public', 'page', 'product', 'App\\Entity\\Product', 'id', null, null);
        $access = new CrudAccessContextDTO(
            $context,
            true,
            true,
            new CrudOwnershipDTO(false, true, false, false, null),
            true,
            false,
            false,
        );
        $object = new \stdClass();
        $objectFinder = $this->createMock(CrudObjectFinderInterface::class);
        $objectFinder->expects(self::never())->method('findAll');
        $accessBuilder = $this->createStub(CrudAccessContextBuilderInterface::class);
        $accessBuilder->method('build')->willReturn($access);
        $routeResolver = $this->createStub(CrudRouteNameResolverInterface::class);

        $provider = new CrudPageDefinitionProvider(
            $objectFinder,
            $this->collectionReader([$object, ['id' => 1]]),
            $accessBuilder,
            $routeResolver,
        );
        $page = $provider->providePage($context);

        self::assertSame('product page', $page->title);
        self::assertSame('page', $page->template);
        self::assertSame([$object], $page->objects);
        self::assertSame([], $page->actions);
        self::assertTrue($page->meta['collectionPage']);
        self::assertSame([['id' => 1]], $page->meta['projectedRows']);
    }

    public function testProvidePageWithObjectBuildsDetailActions(): void
    {
        $context = new CrudContextDTO('public', 'page', 'product', 'App\\Entity\\Product', 'id', 7, 'App\\Form\\ProductType');
        $object = new \stdClass();
        $access = new CrudAccessContextDTO(
            $context,
            true,
            true,
            new CrudOwnershipDTO(true, true, true, false, 'owner'),
            true,
            true,
            false,
        );
        $accessBuilder = $this->createStub(CrudAccessContextBuilderInterface::class);
        $accessBuilder->method('build')->willReturn($access);
        $routeResolver = $this->routeResolver();

        $provider = new CrudPageDefinitionProvider(
            $this->createStub(CrudObjectFinderInterface::class),
            $this->collectionReader(),
            $accessBuilder,
            $routeResolver,
        );
        $page = $provider->providePage($context, $object);

        self::assertSame([$object], $page->objects);
        self::assertCount(2, $page->actions);
        self::assertSame('index', $page->actions[0]->nameEntity);
        self::assertSame('edit', $page->actions[1]->nameEntity);
        self::assertFalse($page->meta['collectionPage']);
        self::assertSame(7, $page->meta['identifierValue']);
    }

    public function testProvideNewAndEditExposeFormAndDeleteAction(): void
    {
        $context = new CrudContextDTO('admin', 'edit', 'product', 'App\\Entity\\Product', 'id', 7, 'App\\Form\\ProductType');
        $object = new \stdClass();
        $access = new CrudAccessContextDTO(
            $context,
            true,
            true,
            new CrudOwnershipDTO(true, true, true, false, 'owner'),
            true,
            true,
            true,
        );
        $accessBuilder = $this->createStub(CrudAccessContextBuilderInterface::class);
        $accessBuilder->method('build')->willReturn($access);
        $provider = new CrudPageDefinitionProvider(
            $this->createStub(CrudObjectFinderInterface::class),
            $this->collectionReader(),
            $accessBuilder,
            $this->routeResolver(),
        );
        $formView = new \stdClass();

        $newPage = $provider->provideNew($context, $object, $formView);
        $editPage = $provider->provideEdit($context, $object, $formView);

        self::assertSame('new', $newPage->template);
        self::assertSame($formView, $newPage->meta['formView']);
        self::assertCount(1, $newPage->actions);
        self::assertSame('edit', $editPage->template);
        self::assertSame($formView, $editPage->meta['formView']);
        self::assertCount(2, $editPage->actions);
        self::assertSame('delete', $editPage->actions[1]->nameEntity);
        self::assertSame('danger', $editPage->actions[1]->scope);
    }

    public function testContextAndOwnershipBehaviorContracts(): void
    {
        $admin = new CrudContextDTO('admin', 'index', 'product', '', 'id', null, null);
        $public = new CrudContextDTO('public', 'index', 'product', '', 'id', null, null);
        self::assertTrue($admin->isAdminView());
        self::assertFalse($public->isAdminView());

        self::assertTrue((new CrudOwnershipDTO(false, false, false, true, null))->canMutate());
        self::assertFalse((new CrudOwnershipDTO(false, true, true, false, null))->canMutate());
        self::assertTrue((new CrudOwnershipDTO(true, true, true, false, 'owner'))->canMutate());
        self::assertFalse((new CrudOwnershipDTO(true, false, true, false, 'owner'))->canMutate());
    }

    private function routeResolver(): CrudRouteNameResolverInterface
    {
        return new class implements CrudRouteNameResolverInterface {
            public function resolveIndex(CrudContextDTO $context): string
            {
                return 'crud_index';
            }

            public function resolveNew(CrudContextDTO $context): string
            {
                return 'crud_new';
            }

            public function resolveShow(CrudContextDTO $context, ?string $identifierField = null): string
            {
                return 'crud_show';
            }

            public function resolveEdit(CrudContextDTO $context, ?string $identifierField = null): string
            {
                return 'crud_edit';
            }

            public function resolveDelete(CrudContextDTO $context, ?string $identifierField = null): string
            {
                return 'crud_delete';
            }

            public function parameters(CrudContextDTO $context, string|int|null $identifierValue = null, ?string $identifierField = null, ?string $operation = null): array
            {
                return ['resourcePath' => $context->resourcePath, 'operation' => $operation ?? $context->operation];
            }
        };
    }

    /** @param list<array<string, mixed>|object> $items */
    private function collectionReader(array $items = []): CollectionRequestReaderInterface
    {
        return new class($items) implements CollectionRequestReaderInterface {
            /** @param list<array<string, mixed>|object> $items */
            public function __construct(private array $items)
            {
            }

            public function read(string $entityClass): CollectionResultDTO
            {
                return new CollectionResultDTO(
                    $this->items,
                    count($this->items),
                    count($this->items),
                    new CollectionPageDTO(1, 25),
                );
            }
        };
    }
}
