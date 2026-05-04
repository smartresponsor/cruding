<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Crud;

final readonly class CrudPageActionDefinition
{
    /**
     * @param array<string, string|int> $routeParameters
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $routeName,
        public array $routeParameters,
        public string $scope = 'page',
        public bool $enabled = true,
    ) {
    }
}
