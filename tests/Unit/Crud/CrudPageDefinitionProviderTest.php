<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud;

use App\Cruding\Dto\Crud\CrudAccessContext;
use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Dto\Crud\CrudOwnership;
use App\Cruding\Service\Crud\CrudPageDefinitionProvider;
use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Crud\CrudRouteNameResolverInterface;
use PHPUnit\Framework\TestCase;

final class CrudPageDefinitionProviderTest extends TestCase
{
    public function testProvideIndexBuildsBridgeReadyPageDefinition(): void
    {
        $context = new CrudContext('public', 'index', 'product', 'App\\Cruding\\Entity\\Product', 'slug', null, 'App\\Cruding\\Form\\ProductType', 'crud');
        $access = new CrudAccessContext(
            $context,
            true,
            true,
            new CrudOwnership(false, true, false, false, null),
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

            public function findOne(CrudContext $context): ?object
            {
                return null;
            }

            public function findAll(CrudContext $context): array
            {
                return $this->objects;
            }
        };

        $accessBuilder = new class($access) implements CrudAccessContextBuilderInterface {
            public function __construct(private CrudAccessContext $access)
            {
            }

            public function build(CrudContext $context, ?object $object = null): CrudAccessContext
            {
                return $this->access;
            }
        };

        $routeResolver = new class implements CrudRouteNameResolverInterface {
            public function resolveIndex(CrudContext $context): string
            {
                return 'cruding_index';
            }

            public function resolveNew(CrudContext $context): string
            {
                return 'cruding_new';
            }

            public function resolveShow(CrudContext $context, ?string $identifierField = null): string
            {
                return 'cruding_show_slug';
            }

            public function resolveEdit(CrudContext $context, ?string $identifierField = null): string
            {
                return 'cruding_edit_slug';
            }

            public function resolveDelete(CrudContext $context, ?string $identifierField = null): string
            {
                return 'cruding_delete_slug';
            }

            public function parameters(CrudContext $context, string|int|null $identifierValue = null, ?string $identifierField = null): array
            {
                return ['resourcePath' => $context->resourcePath];
            }
        };

        $provider = new CrudPageDefinitionProvider($objectFinder, $accessBuilder, $routeResolver);
        $page = $provider->provideIndex($context);

        self::assertSame('product index', $page->title);
        self::assertSame('crud/index.html.twig', $page->template);
        self::assertSame($objects, $page->objects);
        self::assertCount(1, $page->actions);
        self::assertSame('new', $page->actions[0]->name);
        self::assertSame('cruding_new', $page->actions[0]->routeName);
        self::assertSame('product', $page->meta['resourcePath']);
    }

    public function testProvideIndexOmitsCreateActionWhenFormTypeIsMissing(): void
    {
        $context = new CrudContext('public', 'index', 'product', 'App\\Cruding\\Entity\\Product', 'slug', null, null, 'crud');
        $access = new CrudAccessContext(
            $context,
            true,
            true,
            new CrudOwnership(false, true, false, false, null),
            true,
            true,
            true,
        );

        $objectFinder = new class implements CrudObjectFinderInterface {
            public function findOne(CrudContext $context): ?object
            {
                return null;
            }

            public function findAll(CrudContext $context): array
            {
                return [];
            }
        };

        $accessBuilder = new class($access) implements CrudAccessContextBuilderInterface {
            public function __construct(private CrudAccessContext $access)
            {
            }

            public function build(CrudContext $context, ?object $object = null): CrudAccessContext
            {
                return $this->access;
            }
        };

        $routeResolver = new class implements CrudRouteNameResolverInterface {
            public function resolveIndex(CrudContext $context): string
            {
                return 'cruding_index';
            }

            public function resolveNew(CrudContext $context): string
            {
                return 'cruding_new';
            }

            public function resolveShow(CrudContext $context, ?string $identifierField = null): string
            {
                return 'cruding_show_slug';
            }

            public function resolveEdit(CrudContext $context, ?string $identifierField = null): string
            {
                return 'cruding_edit_slug';
            }

            public function resolveDelete(CrudContext $context, ?string $identifierField = null): string
            {
                return 'cruding_delete_slug';
            }

            public function parameters(CrudContext $context, string|int|null $identifierValue = null, ?string $identifierField = null): array
            {
                return ['resourcePath' => $context->resourcePath];
            }
        };

        $provider = new CrudPageDefinitionProvider($objectFinder, $accessBuilder, $routeResolver);
        $page = $provider->provideIndex($context);

        self::assertSame([], $page->actions);
    }

    public function testProvideShowBuildsShellReadyPageDefinition(): void
    {
        $context = new CrudContext('public', 'show', 'vendor', 'App\\Vendoring\\Entity\\Vendor\\VendorEntity', 'id', 13, 'App\\Vendoring\\Form\\VendorType', 'crud');
        $access = new CrudAccessContext(
            $context,
            false,
            true,
            new CrudOwnership(false, true, false, false, null),
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

            public function findOne(CrudContext $context): ?object
            {
                return $this->object;
            }

            public function findAll(CrudContext $context): array
            {
                return [$this->object];
            }
        };

        $accessBuilder = new class($access) implements CrudAccessContextBuilderInterface {
            public function __construct(private CrudAccessContext $access)
            {
            }

            public function build(CrudContext $context, ?object $object = null): CrudAccessContext
            {
                return $this->access;
            }
        };

        $routeResolver = new class implements CrudRouteNameResolverInterface {
            public function resolveIndex(CrudContext $context): string
            {
                return 'cruding_index';
            }

            public function resolveNew(CrudContext $context): string
            {
                return 'cruding_new';
            }

            public function resolveShow(CrudContext $context, ?string $identifierField = null): string
            {
                return 'cruding_show_id';
            }

            public function resolveEdit(CrudContext $context, ?string $identifierField = null): string
            {
                return 'cruding_edit_id';
            }

            public function resolveDelete(CrudContext $context, ?string $identifierField = null): string
            {
                return 'cruding_delete_id';
            }

            public function parameters(CrudContext $context, string|int|null $identifierValue = null, ?string $identifierField = null): array
            {
                return [
                    'resourcePath' => $context->resourcePath,
                    'id' => $identifierValue ?? $context->identifierValue,
                ];
            }
        };

        $provider = new CrudPageDefinitionProvider($objectFinder, $accessBuilder, $routeResolver);
        $page = $provider->provideShow($context, $object);

        self::assertSame('vendor show', $page->title);
        self::assertSame('crud/show.html.twig', $page->template);
        self::assertSame([$object], $page->objects);
        self::assertCount(2, $page->actions);
        self::assertSame('index', $page->actions[0]->name);
        self::assertSame('edit', $page->actions[1]->name);
        self::assertSame('vendor', $page->meta['resourcePath']);
        self::assertSame(13, $page->meta['identifierValue']);
    }
}
