<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Crud;

final readonly class CrudPageDefinition
{
    /**
     * @param list<object>                   $objects
     * @param list<CrudPageActionDefinition> $actions
     * @param array<string, mixed>           $meta
     */
    public function __construct(
        public CrudContext $context,
        public CrudAccessContext $access,
        public string $title,
        public string $template,
        public array $objects = [],
        public array $actions = [],
        public array $meta = [],
    ) {
    }
}
