<?php

declare(strict_types=1);

namespace App\Cruding\Service\Operation;

use App\Collectioning\DTO\CollectionQueryDTO;
use App\Collectioning\ServiceInterface\CollectionDefinitionFactoryInterface;
use App\Collectioning\ServiceInterface\CollectionQueryRequestResolverInterface;
use App\Collectioning\ServiceInterface\CollectionScopedReaderInterface;
use App\Cruding\DTO\CrudBulkMutationContextDTO;
use App\Cruding\DTO\CrudBulkMutationResultDTO;
use App\Cruding\DTO\CrudMutationLifecycleContextDTO;
use App\Cruding\Resolver\CrudBulkMutationHandlerResolver;
use App\Cruding\Service\CrudMutationLifecycleDispatcher;
use App\Cruding\ServiceInterface\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Operation\CrudBulkOperationInterface;
use App\Tabling\DTO\TableDefinitionDTO;
use App\Tabling\Service\TableDataScopeResolver;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Executes host-provided bulk mutations over canonical Collectioning scopes.
 */
final readonly class CrudBulkOperation implements CrudBulkOperationInterface
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CollectionDefinitionFactoryInterface $collectionDefinitionFactory,
        private CollectionQueryRequestResolverInterface $queryResolver,
        private CollectionScopedReaderInterface $scopedReader,
        private TableDataScopeResolver $scopeResolver,
        private CrudBulkMutationHandlerResolver $handlerResolver,
        private CrudAccessContextBuilderInterface $accessContextBuilder,
        private AuthorizationCheckerInterface $authorizationChecker,
        private CrudMutationLifecycleDispatcher $lifecycleDispatcher,
    ) {
    }

    /** Executes a bulk mutation request over a canonical Collectioning data scope. */
    public function handle(Request $request): Response
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return new JsonResponse(['error' => 'crud_context_not_found'], Response::HTTP_NOT_FOUND);
        }

        $action = $this->stringInput($request, '_crud_bulk_action', 'action');
        if ('' === $action) {
            return new JsonResponse(['error' => 'crud_bulk_action_required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $handler = $this->handlerResolver->resolve($action, $context);
        } catch (\InvalidArgumentException) {
            return new JsonResponse(['error' => 'crud_bulk_action_unsupported'], Response::HTTP_BAD_REQUEST);
        }

        $permission = $handler->permission();
        if (null !== $permission && '' !== $permission && !$this->authorizationChecker->isGranted($permission)) {
            throw new AccessDeniedHttpException(sprintf('Bulk mutation "%s" is not authorized.', $action));
        }

        $allowedScopes = $handler->allowedDataScopes();
        $defaultScope = $handler->defaultDataScope();
        if ([] === $allowedScopes || !in_array($defaultScope, $allowedScopes, true)) {
            throw new \LogicException(sprintf('Bulk mutation "%s" has an invalid backend data-scope policy.', $action));
        }

        $scope = $this->stringInput($request, '_crud_bulk_scope', 'scope');
        $scope = '' === $scope ? $defaultScope : $scope;
        if (!in_array($scope, $allowedScopes, true)) {
            return new JsonResponse(['error' => 'crud_bulk_scope_not_allowed'], Response::HTTP_BAD_REQUEST);
        }

        $entityClass = $context->entityClass;
        /** @var class-string $entityClass */
        $definition = $this->collectionDefinitionFactory->create($entityClass);
        $query = $request->attributes->get('_crud_collection_query');
        if (!$query instanceof CollectionQueryDTO) {
            $query = $this->queryResolver->resolve($request, $definition);
        }

        $selected = $request->attributes->get('_crud_selected_values');
        if (null === $selected) {
            $selected = $request->request->all()['selected'] ?? [];
        }
        if (!is_array($selected)) {
            $selected = [];
        }

        try {
            $dataScope = $this->scopeResolver->resolve(
                new TableDefinitionDTO('crud_bulk_'.$context->resourcePath, $definition, []),
                $scope,
                array_values($selected),
            );
        } catch (\InvalidArgumentException|\LogicException) {
            return new JsonResponse(['error' => 'crud_bulk_scope_invalid'], Response::HTTP_BAD_REQUEST);
        }

        $attempted = 0;
        $succeeded = 0;
        $failures = [];
        $mutationContext = new CrudBulkMutationContextDTO($context, $request, $action, $scope);

        foreach ($this->scopedReader->read($definition, $query, $dataScope) as $object) {
            if (!is_object($object)) {
                throw new \LogicException('Bulk mutation scope must yield entity objects.');
            }

            ++$attempted;
            $access = $this->accessContextBuilder->build($context, $object);
            if (!$handler->canMutate($access, $object)) {
                throw new AccessDeniedHttpException(sprintf('Bulk mutation "%s" is not authorized for one or more selected objects.', $action));
            }

            $lifecycleContext = new CrudMutationLifecycleContextDTO($context, $object, $request, $action);

            try {
                $this->lifecycleDispatcher->execute(
                    $lifecycleContext,
                    static function () use ($handler, $object, $mutationContext): void {
                        $handler->mutate($object, $mutationContext);
                    },
                );
                ++$succeeded;
            } catch (\Throwable $exception) {
                if (!$handler->continueOnFailure()) {
                    throw $exception;
                }

                $failures[] = [
                    'error' => 'crud_bulk_mutation_failed',
                    'position' => $attempted,
                ];
            }
        }

        $result = new CrudBulkMutationResultDTO(
            $action,
            $scope,
            $attempted,
            $succeeded,
            count($failures),
            $failures,
        );

        return new JsonResponse($result->toArray(), Response::HTTP_OK);
    }

    private function stringInput(Request $request, string $attribute, string $input): string
    {
        $value = $request->attributes->get($attribute, $request->request->get($input, ''));

        return is_scalar($value) ? trim((string) $value) : '';
    }
}
