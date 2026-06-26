<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Factory\Crud\CrudNotFoundResponseFactory;
use App\Cruding\Runner\Crud\CrudServiceRunner;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CrudServiceDispatchController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudServiceRunner $entrypointRunner,
        private readonly CrudNotFoundResponseFactory $notFoundResponseFactory,
    ) {
    }

    public function __invoke(Request $request): Response|CrudResourceContract
    {
        $operation = $this->operation($request);
        if ('' === $operation) {
            return $this->notFoundResponseFactory->create($request, 'crud_operation_token_not_found');
        }

        $request->attributes->set('_crud_operation', $operation);
        $request->attributes->set('_crud_view', (string) $request->attributes->get('_crud_view', 'public'));

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

    private function syntheticContext(Request $request, string $operation): CrudContext
    {
        $identifierField = $request->attributes->has('id') ? 'id' : 'slug';
        $identifierValue = $request->attributes->get($identifierField);

        return new CrudContext(
            view: (string) $request->attributes->get('_crud_view', 'public'),
            operation: $operation,
            resourcePath: trim((string) $request->attributes->get('resourcePath', ''), '/'),
            entityClass: '',
            identifierField: $identifierField,
            identifierValue: is_scalar($identifierValue) ? $identifierValue : null,
            formTypeClass: null,
        );
    }
}
