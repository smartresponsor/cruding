<?php

declare(strict_types=1);

namespace App\Cruding\DTO\Api;

/**
 * Carries normalized API-facing field capabilities without exposing Symfony form internals.
 */
final readonly class CrudApiFieldCapabilityDTO
{
    /**
     * @param list<array{label: string, value: string|int|float|bool|null}> $choices
     */
    public function __construct(
        public string $name,
        public bool $required,
        public bool $readOnly,
        public bool $multiple,
        public array $choices = [],
        public ?int $minLength = null,
        public ?int $maxLength = null,
        public int|float|null $min = null,
        public int|float|null $max = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $payload = [
            'name' => $this->name,
            'required' => $this->required,
            'readOnly' => $this->readOnly,
            'multiple' => $this->multiple,
        ];

        if ([] !== $this->choices) {
            $payload['choices'] = $this->choices;
        }
        if (null !== $this->minLength) {
            $payload['minLength'] = $this->minLength;
        }
        if (null !== $this->maxLength) {
            $payload['maxLength'] = $this->maxLength;
        }
        if (null !== $this->min) {
            $payload['min'] = $this->min;
        }
        if (null !== $this->max) {
            $payload['max'] = $this->max;
        }

        return $payload;
    }
}
