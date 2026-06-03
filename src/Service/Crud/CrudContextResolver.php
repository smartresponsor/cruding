<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\Exception\Crud\CrudResourceNotFoundException;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class CrudContextResolver implements CrudContextResolverInterface
{
    public function __construct(
        private CrudEntityClassResolver $entityClassResolver,
        private CrudFormTypeResolver $formTypeResolver,
        private CrudResourcePathParser $resourcePathParser,
    ) {
    }

    public function resolve(Request $request): CrudContext
    {
        $context = $this->tryResolve($request);
        if (null !== $context) {
            return $context;
        }

        $resourcePath = (string) $request->attributes->get('resourcePath', '');
        throw CrudResourceNotFoundException::forResourcePath($resourcePath);
    }

    public function tryResolve(Request $request): ?CrudContext
    {
        $resourcePath = $this->resourcePathParser->normalize((string) $request->attributes->get('resourcePath', ''));
        if ('' === $resourcePath) {
            return null;
        }

        $surface = (string) $request->attributes->get('_crud_surface', 'public');
        $operation = (string) $request->attributes->get('_crud_operation', 'index');
        $entityClass = $this->entityClassResolver->tryResolve($resourcePath);
        if (null === $entityClass) {
            return null;
        }

        $identifierField = $request->attributes->has('id') ? 'id' : 'slug';
        $identifierValue = $request->attributes->get($identifierField);

        return new CrudContext(
            surface: $surface,
            operation: $operation,
            resourcePath: $resourcePath,
            entityClass: $entityClass,
            identifierField: $identifierField,
            identifierValue: is_scalar($identifierValue) ? $identifierValue : null,
            formTypeClass: $this->formTypeResolver->resolve($entityClass),
        );
    }
}
