<?php

declare(strict_types=1);

namespace App\Cruding\Tests\Unit\Crud\Operation;

use App\Collectioning\DTO\CollectionDataScopeDTO;
use App\Collectioning\DTO\CollectionDefinitionDTO;
use App\Collectioning\DTO\CollectionFieldPolicyDTO;
use App\Collectioning\DTO\CollectionPageDTO;
use App\Collectioning\DTO\CollectionQueryDTO;
use App\Collectioning\ServiceInterface\CollectionDefinitionFactoryInterface;
use App\Collectioning\ServiceInterface\CollectionQueryRequestResolverInterface;
use App\Collectioning\ServiceInterface\CollectionScopedReaderInterface;
use App\Cruding\DTO\CrudAccessContextDTO;
use App\Cruding\DTO\CrudBulkMutationContextDTO;
use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\DTO\CrudOwnershipDTO;
use App\Cruding\Resolver\CrudBulkMutationHandlerResolver;
use App\Cruding\Service\CrudMutationLifecycleDispatcher;
use App\Cruding\Service\Operation\CrudBulkOperation;
use App\Cruding\ServiceInterface\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\CrudBulkMutationHandlerInterface;
use App\Cruding\ServiceInterface\CrudContextResolverInterface;
use App\Tabling\Service\TableDataScopeResolver;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class CrudBulkOperationTest extends TestCase
{
    public function testExecutesSelectedScopeWithLifecycleAndCanonicalSelectionFilter(): void
    {
        $first = new \stdClass();
        $second = new \stdClass();
        $mutated = [];

        $handler = $this->handler(
            [CollectionDataScopeDTO::SELECTED],
            CollectionDataScopeDTO::SELECTED,
            null,
            true,
            false,
            static function (object $object) use (&$mutated): void {
                $mutated[] = $object;
            },
        );

        [$operation, $reader] = $this->operation($handler, [$first, $second]);

        $request = new Request([], ['action' => 'archive', 'scope' => 'selected', 'selected' => [10, 20]]);
        $request->attributes->set('_crud_collection_query', new CollectionQueryDTO(new CollectionPageDTO()));

        $response = $operation->handle($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(2, $payload['attempted']);
        self::assertSame(2, $payload['succeeded']);
        self::assertSame(0, $payload['failed']);
        self::assertSame([$first, $second], $mutated);

        $readerScope = $reader->scope;
        self::assertInstanceOf(CollectionDataScopeDTO::class, $readerScope);
        self::assertSame(CollectionDataScopeDTO::SELECTED, $readerScope->mode);
        self::assertSame('id', $readerScope->selectionFilters[0]->field);
        self::assertSame('in', $readerScope->selectionFilters[0]->operator);
        self::assertSame([10, 20], $readerScope->selectionFilters[0]->value);
    }

    public function testAllowsFilteredAndCurrentPageScopesWithoutSelectionLanguage(): void
    {
        foreach ([CollectionDataScopeDTO::FILTERED, CollectionDataScopeDTO::CURRENT_PAGE] as $scope) {
            $handler = $this->handler(
                [CollectionDataScopeDTO::FILTERED, CollectionDataScopeDTO::CURRENT_PAGE],
                CollectionDataScopeDTO::FILTERED,
            );
            [$operation, $reader] = $this->operation($handler, [new \stdClass()]);

            $request = new Request([], ['action' => 'archive', 'scope' => $scope]);
            $request->attributes->set('_crud_collection_query', new CollectionQueryDTO(new CollectionPageDTO()));

            $response = $operation->handle($request);

            self::assertSame(200, $response->getStatusCode());
            self::assertInstanceOf(CollectionDataScopeDTO::class, $reader->scope);
            self::assertSame($scope, $reader->scope->mode);
            self::assertSame([], $reader->scope->selectionFilters);
        }
    }

    public function testRejectsUnauthorizedOperationBeforeReadingScope(): void
    {
        $handler = $this->handler([CollectionDataScopeDTO::FILTERED], CollectionDataScopeDTO::FILTERED, 'BULK_ARCHIVE');
        [$operation] = $this->operation($handler, [], false);

        $request = new Request([], ['action' => 'archive']);

        $this->expectException(AccessDeniedHttpException::class);
        $operation->handle($request);
    }

    public function testRejectsUnsupportedActionAndMalformedSelection(): void
    {
        [$unsupported] = $this->operation($this->handler([CollectionDataScopeDTO::SELECTED], CollectionDataScopeDTO::SELECTED), []);

        $request = new Request([], ['action' => 'unknown']);
        $response = $unsupported->handle($request);
        self::assertSame(400, $response->getStatusCode());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        self::assertSame('crud_bulk_action_unsupported', $payload['error']);
        self::assertArrayNotHasKey('message', $payload);

        [$selected] = $this->operation($this->handler([CollectionDataScopeDTO::SELECTED], CollectionDataScopeDTO::SELECTED), []);
        $request = new Request([], ['action' => 'archive', 'scope' => 'selected', 'selected' => []]);
        $request->attributes->set('_crud_collection_query', new CollectionQueryDTO(new CollectionPageDTO()));

        $response = $selected->handle($request);
        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('crud_bulk_scope_invalid', (string) $response->getContent());

        $request = new Request([], ['action' => 'archive', 'scope' => 'selected', 'selected' => 'not-a-list']);
        $request->attributes->set('_crud_collection_query', new CollectionQueryDTO(new CollectionPageDTO()));
        $response = $selected->handle($request);
        self::assertSame(400, $response->getStatusCode());
        self::assertStringContainsString('crud_bulk_scope_invalid', (string) $response->getContent());
    }

    public function testControllerDispatchesBulkToBulkOperationBoundary(): void
    {
        $source = file_get_contents(dirname(__DIR__, 4).'/src/Controller/CrudController.php');
        self::assertIsString($source);
        self::assertStringContainsString("'bulk' => 'bulk'", $source);
        self::assertStringNotContainsString("'bulk' => 'create'", $source);
        self::assertStringContainsString('CrudBulkOperationInterface', $source);
    }

    public function testContinueOnFailureReportsPartialBatchResult(): void
    {
        $calls = 0;
        $handler = $this->handler(
            [CollectionDataScopeDTO::FILTERED],
            CollectionDataScopeDTO::FILTERED,
            null,
            true,
            true,
            static function () use (&$calls): void {
                ++$calls;
                if (1 === $calls) {
                    throw new \RuntimeException('first failed');
                }
            },
        );
        [$operation] = $this->operation($handler, [new \stdClass(), new \stdClass()]);

        $request = new Request([], ['action' => 'archive']);
        $request->attributes->set('_crud_collection_query', new CollectionQueryDTO(new CollectionPageDTO()));

        $response = $operation->handle($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);

        self::assertSame(2, $payload['attempted']);
        self::assertSame(1, $payload['succeeded']);
        self::assertSame(1, $payload['failed']);
        self::assertIsArray($payload['failures']);
        self::assertIsArray($payload['failures'][0]);
        self::assertSame('crud_bulk_mutation_failed', $payload['failures'][0]['error']);
        self::assertSame(1, $payload['failures'][0]['position']);
        self::assertArrayNotHasKey('message', $payload['failures'][0]);
        self::assertArrayNotHasKey('objectClass', $payload['failures'][0]);
    }

    /**
     * @param list<object> $objects
     *
     * @return array{CrudBulkOperation, object{scope: ?CollectionDataScopeDTO}}
     */
    private function operation(
        CrudBulkMutationHandlerInterface $handler,
        array $objects,
        bool $operationGranted = true,
    ): array {
        $context = new CrudContextDTO('table', 'bulk', 'user', \stdClass::class, 'id', null, null);
        $contextResolver = $this->createStub(CrudContextResolverInterface::class);
        $contextResolver->method('tryResolve')->willReturn($context);

        $definition = new CollectionDefinitionDTO(
            \stdClass::class,
            [new CollectionFieldPolicyDTO('id', filterable: true, filterOperators: ['eq', 'in'])],
            identifierFields: ['id'],
        );
        $definitionFactory = $this->createStub(CollectionDefinitionFactoryInterface::class);
        $definitionFactory->method('create')->willReturn($definition);

        $queryResolver = $this->createStub(CollectionQueryRequestResolverInterface::class);

        $reader = new class($objects) implements CollectionScopedReaderInterface {
            public ?CollectionDataScopeDTO $scope = null;

            /** @param list<object> $objects */
            public function __construct(private array $objects)
            {
            }

            public function read(CollectionDefinitionDTO $definition, CollectionQueryDTO $query, CollectionDataScopeDTO $scope): iterable
            {
                $this->scope = $scope;

                return $this->objects;
            }
        };

        $access = new CrudAccessContextDTO(
            $context,
            true,
            true,
            new CrudOwnershipDTO(false, true, false, true, null),
            true,
            true,
            true,
        );
        $accessBuilder = $this->createStub(CrudAccessContextBuilderInterface::class);
        $accessBuilder->method('build')->willReturn($access);

        $authorization = $this->createStub(AuthorizationCheckerInterface::class);
        $authorization->method('isGranted')->willReturn($operationGranted);

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn(null);
        $managerRegistry->method('getManager')->willReturn($this->createStub(ObjectManager::class));

        return [
            new CrudBulkOperation(
                $contextResolver,
                $definitionFactory,
                $queryResolver,
                $reader,
                new TableDataScopeResolver(),
                new CrudBulkMutationHandlerResolver([$handler]),
                $accessBuilder,
                $authorization,
                new CrudMutationLifecycleDispatcher([], $managerRegistry),
            ),
            $reader,
        ];
    }

    /** @param list<string> $allowedScopes */
    private function handler(
        array $allowedScopes,
        string $defaultScope,
        ?string $permission = null,
        bool $canMutate = true,
        bool $continueOnFailure = false,
        ?\Closure $mutator = null,
    ): CrudBulkMutationHandlerInterface {
        return new class($allowedScopes, $defaultScope, $permission, $canMutate, $continueOnFailure, $mutator) implements CrudBulkMutationHandlerInterface {
            /** @param list<string> $allowedScopes */
            public function __construct(
                private array $allowedScopes,
                private string $defaultScope,
                private ?string $permission,
                private bool $canMutate,
                private bool $continueOnFailure,
                private ?\Closure $mutator,
            ) {
            }

            public function supports(string $action, CrudContextDTO $context): bool
            {
                return 'archive' === $action;
            }

            public function allowedDataScopes(): array
            {
                return $this->allowedScopes;
            }

            public function defaultDataScope(): string
            {
                return $this->defaultScope;
            }

            public function permission(): ?string
            {
                return $this->permission;
            }

            public function canMutate(CrudAccessContextDTO $access, object $object): bool
            {
                return $this->canMutate;
            }

            public function continueOnFailure(): bool
            {
                return $this->continueOnFailure;
            }

            public function mutate(object $object, CrudBulkMutationContextDTO $context): void
            {
                if (null !== $this->mutator) {
                    ($this->mutator)($object, $context);
                }
            }
        };
    }
}
