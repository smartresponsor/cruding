<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud\Operation;

use App\Cruding\DTO\CrudAccessContextDTO;
use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\CrudOwnershipDTO;
use App\Cruding\DTO\CrudPageDefinitionDTO;
use App\Cruding\Factory\CrudNotFoundResponseFactory;
use App\Cruding\Factory\Resource\CrudResourceContractFactory;
use App\Cruding\Service\Operation\CrudIndexOperation;
use App\Cruding\ServiceInterface\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\CrudPageDefinitionProviderInterface;
use App\Cruding\ServiceInterface\Entrypoint\CrudServiceDispatcherInterface;
use App\Cruding\ServiceInterface\Resource\CrudInterfacingProviderResourceBuilderInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class CrudIndexOperationTest extends TestCase
{
    public function testBuildsIndexviewFromResolvedContext(): void
    {
        $request = Request::create('/document/index');
        $context = new CrudContextDTO(
            view: 'public',
            operation: 'index',
            resourcePath: 'document',
            entityClass: 'App\\Tests\\Fixture\\Entity\\DocumentEntity',
            identifierField: 'id',
            identifierValue: null,
            formTypeClass: null,
        );
        $access = new CrudAccessContextDTO(
            crud: $context,
            supportsSlug: false,
            supportsId: true,
            ownership: new CrudOwnershipDTO(false, false, false, false, null),
            canView: true,
            canEdit: false,
            canDelete: false,
        );
        $page = new CrudPageDefinitionDTO($context, $access, 'Documents', '@Cruding/crud/index.html.twig');

        $contextResolver = $this->createMock(CrudContextResolverInterface::class);
        $contextResolver->expects(self::once())
            ->method('tryResolve')
            ->with($request)
            ->willReturn($context);

        $entrypointDispatcher = $this->createMock(CrudServiceDispatcherInterface::class);
        $entrypointDispatcher->expects(self::once())
            ->method('tryRun')
            ->with($request, $context)
            ->willReturn(null);

        $pageDefinitionProvider = $this->createMock(CrudPageDefinitionProviderInterface::class);
        $pageDefinitionProvider->expects(self::once())
            ->method('provideIndex')
            ->with($context)
            ->willReturn($page);

        $viewBuilder = $this->createStub(CrudInterfacingProviderResourceBuilderInterface::class);
        $viewBuilder->method('build')->willReturn([]);

        $operation = new CrudIndexOperation(
            $contextResolver,
            $pageDefinitionProvider,
            new CrudResourceContractFactory($viewBuilder),
            new CrudNotFoundResponseFactory(),
            $entrypointDispatcher,
        );

        self::assertInstanceOf(CrudResourceContract::class, $operation->handle($request));
    }
}
