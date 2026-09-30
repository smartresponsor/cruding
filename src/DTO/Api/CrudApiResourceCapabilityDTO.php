<?php

declare(strict_types=1);

namespace App\Cruding\DTO\Api;

/**
 * Carries the stable external CRUD capability contract for one resolved resource.
 */
final readonly class CrudApiResourceCapabilityDTO
{
    /**
     * @param array{supportsId: bool, supportsSlug: bool}                              $identity
     * @param array{index: bool, show: bool, create: bool, update: bool, delete: bool} $operations
     * @param list<CrudApiFieldCapabilityDTO>                                          $fields
     */
    public function __construct(
        public string $resource,
        public array $identity,
        public array $operations,
        public array $fields,
    ) {
    }

    /**
     * @return array{
     *     schemaVersion: int,
     *     resource: string,
     *     identity: array{supportsId: bool, supportsSlug: bool},
     *     operations: array{index: bool, show: bool, create: bool, update: bool, delete: bool},
     *     fields: list<array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            'schemaVersion' => 1,
            'resource' => $this->resource,
            'identity' => $this->identity,
            'operations' => $this->operations,
            'fields' => array_map(
                static fn (CrudApiFieldCapabilityDTO $field): array => $field->toArray(),
                $this->fields,
            ),
        ];
    }
}
