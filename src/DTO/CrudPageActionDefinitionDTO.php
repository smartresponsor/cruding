<?php

declare(strict_types=1);

namespace App\Cruding\Dto;

/**
 * Carries page action definition data across Cruding processing boundaries.
 */
final readonly class CrudPageActionDefinitionDTO
{
    /**
     * @param array<string, string|int> $routeParameters
     */
    public function __construct(
        public string $nameEntity,
        public string $label,
        public string $routeName,
        public array $routeParameters,
        public string $scope = 'page',
        public bool $enabled = true,
    ) {
    }
}
