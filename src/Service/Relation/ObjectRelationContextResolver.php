<?php

declare(strict_types=1);

namespace App\Cruding\Service\Relation;

use App\Cruding\Dto\Relation\ObjectRelationContext;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Relation\ObjectRelationContextResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ObjectRelationContextResolver implements ObjectRelationContextResolverInterface
{
    /** @param array<string,array<string,string>> $relationMap */
    public function __construct(
        private CrudContextResolverInterface $crudContextResolver,
        private array $relationMap = [],
    ) {
    }

    public function resolve(Request $request): ObjectRelationContext
    {
        $crud = $this->crudContextResolver->resolve($request);
        $relationKind = (string) $request->attributes->get('relationKind', '');
        $config = $this->relationMap[$relationKind] ?? null;
        if (!is_array($config)) {
            throw new NotFoundHttpException(sprintf('Relation kind "%s" is not configured.', $relationKind));
        }

        $relatedSlug = $request->attributes->get('relatedSlug');
        if (!is_string($relatedSlug) || '' === $relatedSlug) {
            $relatedSlug = $request->request->getString('relatedSlug', '');
        }
        if ('' === $relatedSlug && str_contains((string) $request->headers->get('Content-Type', ''), 'json')) {
            $payload = json_decode((string) $request->getContent(), true);
            if (is_array($payload) && isset($payload['relatedSlug']) && is_string($payload['relatedSlug'])) {
                $relatedSlug = $payload['relatedSlug'];
            }
        }

        return new ObjectRelationContext(
            crud: $crud,
            relationKind: $relationKind,
            collectionGetter: (string) ($config['collection_getter'] ?? ''),
            addMethod: (string) ($config['add_method'] ?? ''),
            removeMethod: (string) ($config['remove_method'] ?? ''),
            targetClass: ($config['target_class'] ?? null) ?: null,
            targetIdentifierField: (string) ($config['target_identifier_field'] ?? 'slug'),
            relatedSlug: '' !== $relatedSlug ? $relatedSlug : null,
        );
    }
}
