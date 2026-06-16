<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud\Operation;

use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointContext;
use App\Cruding\Service\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\Entrypoint\CrudEntrypointClassNameResolver;
use App\Cruding\Service\Crud\Entrypoint\CrudEntrypointExplicitServiceResolver;
use App\Cruding\Service\Crud\Entrypoint\CrudEntrypointInvoker;
use App\Cruding\Service\Crud\Entrypoint\CrudEntrypointOperationRunner;
use App\Cruding\Service\Crud\Entrypoint\CrudEntrypointResolver;
use App\Cruding\Service\Crud\Entrypoint\NullCrudEntrypointService;
use App\Cruding\Service\Crud\Operation\CrudIndexOperation;
use App\Cruding\Service\Crud\Surface\CrudSurfaceContractFactory;
use App\Cruding\Service\Surface\CrudSurfaceServiceLocator;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\Crud\Surface\CrudInterfacingProviderSurfaceBuilderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class CrudIndexOperationTest extends TestCase
{
    public function testExplicitRouteServiceRunsBeforeContextResolution(): void
    {
        $request = Request::create('/vendor/attachment/document/index');
        $request->attributes->set('_crud_service', 'App\\Vendoring\\Service\\Http\\Vendor\\Attachment\\Document\\VendorAttachmentDocumentIndexService');
        $request->attributes->set('_crud_surface', 'public');
        $request->attributes->set('_crud_operation', 'index');
        $request->attributes->set('resourcePath', 'vendor/attachment/document');

        $contextResolver = $this->createMock(CrudContextResolverInterface::class);
        $contextResolver->expects(self::never())
            ->method('tryResolve');

        $routeService = new class {
            public function get(CrudEntrypointContext $context): JsonResponse
            {
                return new JsonResponse(['ok' => true, 'resourcePath' => $context->resourcePath()]);
            }
        };

        $serviceLocator = new CrudSurfaceServiceLocator(new class($routeService) implements ContainerInterface {
            public function __construct(private object $service)
            {
            }

            public function get(string $id): mixed
            {
                if ('App\\Vendoring\\Service\\Http\\Vendor\\Attachment\\Document\\VendorAttachmentDocumentIndexService' !== $id) {
                    throw new \RuntimeException(sprintf('Unknown service "%s".', $id));
                }

                return $this->service;
            }

            public function has(string $id): bool
            {
                return 'App\\Vendoring\\Service\\Http\\Vendor\\Attachment\\Document\\VendorAttachmentDocumentIndexService' === $id;
            }
        });

        $entrypointRunner = new CrudEntrypointOperationRunner(
            new CrudEntrypointResolver(
                new CrudEntrypointExplicitServiceResolver(),
                new CrudEntrypointClassNameResolver(),
                $serviceLocator,
                new NullCrudEntrypointService(),
            ),
            new CrudEntrypointInvoker(),
        );

        $surfaceBuilder = $this->createStub(CrudInterfacingProviderSurfaceBuilderInterface::class);
        $surfaceBuilder->method('build')->willReturn([]);

        $operation = new CrudIndexOperation(
            $contextResolver,
            $this->createStub(CrudPageDefinitionProviderInterface::class),
            new CrudSurfaceContractFactory($surfaceBuilder),
            new CrudNotFoundResponseFactory(),
            $entrypointRunner,
        );

        $response = $operation->handle($request);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(['ok' => true, 'resourcePath' => 'vendor/attachment/document'], json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }
}
