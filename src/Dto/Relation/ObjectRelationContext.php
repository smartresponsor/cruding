<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Relation;

use App\Cruding\Dto\Crud\CrudContext;

final readonly class ObjectRelationContext
{
    public function __construct(
        public CrudContext $crud,
        public string $relationKind,
        public string $collectionGetter,
        public string $addMethod,
        public string $removeMethod,
        public ?string $targetClass,
        public string $targetIdentifierField,
        public ?string $relatedSlug = null,
    ) {
    }
}
