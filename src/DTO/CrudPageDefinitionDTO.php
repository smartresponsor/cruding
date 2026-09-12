<?php

declare(strict_types=1);

namespace App\Cruding\Dto;

/**
 * Carries page definition data across Cruding processing boundaries.
 */
final readonly class CrudPageDefinitionDTO
{
    /**
     * @param list<object>                      $objects
     * @param list<CrudPageActionDefinitionDTO> $actions
     * @param array<string, mixed>              $meta
     */
    public function __construct(
        public CrudContextDTO $context,
        public CrudAccessContextDTO $access,
        public string $title,
        public string $template,
        public array $objects = [],
        public array $actions = [],
        public array $meta = [],
    ) {
    }
}
