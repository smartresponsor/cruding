<?php

declare(strict_types=1);

namespace App\Cruding\Controller;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\Factory\CrudNotFoundResponseFactory;
use App\Cruding\Runner\CrudServiceRunner;
use App\Cruding\ServiceInterface\CrudContextResolverInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles service dispatch controller HTTP requests at the Cruding controller boundary.
 */
final class CrudServiceDispatchController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudServiceRunner $entrypointRunner,
        private readonly CrudNotFoundResponseFactory $notFoundResponseFactory,
    ) {
    }

    /**      * Handles the invokable HTTP or application entrypoint.      */
    public function __invoke(Request $request): Response|CrudResourceContract
    {
        $operation = $this->operation($request);
        if ('' === $operation) {
            return $this->notFoundResponseFactory->create($request, 'crud_operation_token_not_found');
        }

        $request->attributes->set('_crud_operation', $operation);
        $view = $request->attributes->get('_crud_view', 'public');
        $request->attributes->set('_crud_view', is_scalar($view) ? (string) $view : 'public');

        $context = $this->contextResolver->tryResolve($request) ?? $this->syntheticContext($request, $operation);
        $result = $this->entrypointRunner->run($request, $context);
        $payload = $result->payload();
        if (null !== $payload) {
            return $payload;
        }

        return $this->notFoundResponseFactory->create($request, 'crud_entrypoint_not_found', [
            'operationToken' => $operation,
            'entrypointTrace' => $result->diagnostics()['entrypointTrace'] ?? $result->diagnostics(),
            'interpretation' => 'Configured CRUD operation token route matched, but no URI-derived or explicit entrypoint returned a response or view contract.',
        ]);
    }

    private function operation(Request $request): string
    {
        $value = $request->attributes->get('operationToken', $request->attributes->get('_crud_operation', ''));
        if (!is_scalar($value)) {
            return '';
        }

        return strtolower(trim((string) $value));
    }

    private function syntheticContext(Request $request, string $operation): CrudContextDTO
    {
        $identifierField = $request->attributes->has('id') ? 'id' : 'slug';
        $identifierValue = $request->attributes->get($identifierField);
        $view = $request->attributes->get('_crud_view', 'public');
        $resourcePath = $request->attributes->get('resourcePath', '');

        return new CrudContextDTO(
            view: is_scalar($view) ? (string) $view : 'public',
            operation: $operation,
            resourcePath: is_scalar($resourcePath) ? trim((string) $resourcePath, '/') : '',
            entityClass: '',
            identifierField: $identifierField,
            identifierValue: is_int($identifierValue) || is_string($identifierValue) ? $identifierValue : null,
            formTypeClass: null,
        );
    }
}
