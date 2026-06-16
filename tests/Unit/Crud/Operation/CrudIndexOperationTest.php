<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud\Operation;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Service\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Service\Crud\Operation\CrudIndexOperation;
use App\Cruding\Service\Crud\Surface\CrudSurfaceContractFactory;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\Crud\Surface\CrudInterfacingProviderSurfaceBuilderInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class CrudIndexOperationTest extends TestCase
{
    public function testBuildsIndexSurfaceFromResolvedContext(): void
    {
        $request = Request::create('/document/index');
        $context = new CrudContext(
            surface: 'public',
            operation: 'index',
            resourcePath: 'document',
            entityClass: 'App\\Tests\\Fixture\\Entity\\DocumentEntity',
            identifierField: 'id',
            identifierValue: null,
            formTypeClass: null,
        );

        $contextResolver = $this->createMock(CrudContextResolverInterface::class);
        $contextResolver->expects(self::once())
            ->method('tryResolve')
            ->with($request)
            ->willReturn($context);

        $pageDefinitionProvider = $this->createMock(CrudPageDefinitionProviderInterface::class);
        $pageDefinitionProvider->expects(self::once())
            ->method('provideIndex')
            ->with($context);

        $surfaceBuilder = $this->createStub(CrudInterfacingProviderSurfaceBuilderInterface::class);
        $surfaceBuilder->method('build')->willReturn([]);

        $operation = new CrudIndexOperation(
            $contextResolver,
            $pageDefinitionProvider,
            new CrudSurfaceContractFactory($surfaceBuilder),
            new CrudNotFoundResponseFactory(),
        );

        self::assertInstanceOf(CrudSurfaceContract::class, $operation->handle($request));
    }
}
