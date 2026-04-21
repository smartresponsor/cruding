<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudTemplateResolverInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class CrudContextResolver implements CrudContextResolverInterface
{
    public function __construct(
        private CrudEntityClassResolver $entityClassResolver,
        private CrudFormTypeResolver $formTypeResolver,
        private CrudTemplateResolverInterface $templateResolver,
    ) {
    }

    public function resolve(Request $request): CrudContext
    {
        $resourcePath = (string) $request->attributes->get('resourcePath', '');
        $surface = (string) $request->attributes->get('_crud_surface', 'public');
        $operation = (string) $request->attributes->get('_crud_operation', 'index');
        $entityClass = $this->entityClassResolver->resolve($resourcePath);

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
            templatePrefix: $this->templateResolver->resolvePrefix($resourcePath),
        );
    }
}
