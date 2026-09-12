<?php

declare(strict_types=1);

namespace App\Cruding\Resolver;

use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\Exception\CrudResourceNotFoundException;
use App\Cruding\Parser\CrudResourcePathParser;
use App\Cruding\ServiceInterface\CrudContextResolverInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Resolves context resolver for Cruding request processing.
 */
final readonly class CrudContextResolver implements CrudContextResolverInterface
{
    public function __construct(
        private CrudEntityClassResolver $entityClassResolver,
        private CrudFormTypeResolver $formTypeResolver,
        private CrudResourcePathParser $resourcePathParser,
    ) {
    }

    /**      * Executes the resolve operation.      */
    public function resolve(Request $request): CrudContextDTO
    {
        $context = $this->tryResolve($request);
        if (null !== $context) {
            return $context;
        }

        $resourcePath = (string) $request->attributes->get('resourcePath', '');
        throw CrudResourceNotFoundException::forResourcePath($resourcePath);
    }

    /**      * Executes the try resolve operation.      */
    public function tryResolve(Request $request): ?CrudContextDTO
    {
        $resourcePath = $this->resourcePathParser->normalize((string) $request->attributes->get('resourcePath', ''));
        if ('' === $resourcePath) {
            return null;
        }

        $view = (string) $request->attributes->get('_crud_view', 'public');
        $operation = (string) $request->attributes->get('_crud_operation', 'index');
        $entityClass = $this->entityClassResolver->tryResolve($resourcePath);
        if (null === $entityClass) {
            return null;
        }

        $identifierField = $request->attributes->has('id') ? 'id' : 'slug';
        $identifierValue = $request->attributes->get($identifierField);

        return new CrudContextDTO(
            view: $view,
            operation: $operation,
            resourcePath: $resourcePath,
            entityClass: $entityClass,
            identifierField: $identifierField,
            identifierValue: is_scalar($identifierValue) ? $identifierValue : null,
            formTypeClass: $this->formTypeResolver->resolve($entityClass),
        );
    }
}
