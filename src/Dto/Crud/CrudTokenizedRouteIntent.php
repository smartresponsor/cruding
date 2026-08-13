<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Crud;

final readonly class CrudTokenizedRouteIntent
{
    /**
     * @param list<string> $tokens
     */
    public function __construct(
        public string $routeFamily,
        public string $resourcePath,
        public string $operation,
        public string $view,
        public ?string $identifierField,
        public string|int|null $identifierValue,
        public array $tokens,
    ) {
    }

    public function hasIdentity(): bool
    {
        return null !== $this->identifierField && null !== $this->identifierValue;
    }

    /**
     * @return array<string, scalar|list<string>|null>
     */
    public function diagnostics(): array
    {
        return [
            'routeFamily' => $this->routeFamily,
            'resourcePath' => $this->resourcePath,
            'operation' => $this->operation,
            'view' => $this->view,
            'identifierField' => $this->identifierField,
            'identifierValue' => is_scalar($this->identifierValue) ? $this->identifierValue : null,
            'tokens' => $this->tokens,
        ];
    }
}
