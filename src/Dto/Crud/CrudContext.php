<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Crud;

final readonly class CrudContext
{
    public function __construct(
        public string $surface,
        public string $operation,
        public string $resourcePath,
        public string $entityClass,
        public string $identifierField,
        public string|int|null $identifierValue,
        public ?string $formTypeClass,
        public string $templatePrefix,
    ) {
    }

    public function isAdminSurface(): bool
    {
        return 'admin' === $this->surface;
    }

    public function template(string $name): string
    {
        return sprintf('%s/%s.html.twig', trim($this->templatePrefix, '/'), $name);
    }
}
