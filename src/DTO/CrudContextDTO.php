<?php

declare(strict_types=1);

namespace App\Cruding\DTO;

/**
 * Carries context data across Cruding processing boundaries.
 */
final readonly class CrudContextDTO
{
    public function __construct(
        public string $view,
        public string $operation,
        public string $resourcePath,
        public string $entityClass,
        public string $identifierField,
        public string|int|null $identifierValue,
        public ?string $formTypeClass,
    ) {
    }

    /**      * Indicates whether admin view.      */
    public function isAdminView(): bool
    {
        return 'admin' === $this->view;
    }
}
