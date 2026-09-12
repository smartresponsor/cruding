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

        $resourcePath = $this->attributeString($request, 'resourcePath', '');
        throw CrudResourceNotFoundException::forResourcePath($resourcePath);
    }

    /**      * Executes the try resolve operation.      */
    public function tryResolve(Request $request): ?CrudContextDTO
    {
        $resourcePath = $this->resourcePathParser->normalize($this->attributeString($request, 'resourcePath', ''));
        if ('' === $resourcePath) {
            return null;
        }

        $view = $this->attributeString($request, '_crud_view', 'public');
        $operation = $this->attributeString($request, '_crud_operation', 'index');
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
            identifierValue: is_int($identifierValue) || is_string($identifierValue) ? $identifierValue : null,
            formTypeClass: $this->formTypeResolver->resolve($entityClass),
        );
    }

    private function attributeString(Request $request, string $name, string $default): string
    {
        $value = $request->attributes->get($name, $default);

        return is_scalar($value) ? (string) $value : $default;
    }
}
