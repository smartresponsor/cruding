<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Surface;

/**
 * Describes a resource-bound route after Cruding has parsed the route tokens.
 *
 * The route path is the producer-side declaration. This value object keeps the
 * controller independent from producer-specific payload volume, template shape,
 * and service implementation details. Template candidates are diagnostic hints only;
 * Viewing owns the runtime render/fallback decision.
 */
final readonly class CrudRouteContext
{
    /**
     * @param array<string, string|int|null> $routeParameters
     * @param list<string>                   $providerKeys
     * @param list<string>                   $templateCandidates diagnostic folder/index hints only, not a runtime render contract
     */
    public function __construct(
        public string $resource,
        public string $resourcePath,
        public string $operation,
        public string $view,
        public ?string $surfacePath,
        public ?string $subjectField,
        public string|int|null $subjectValue,
        public ?string $itemField,
        public string|int|null $itemValue,
        public ?string $routeName,
        public ?string $routeTemplate,
        public array $routeParameters,
        public array $providerKeys,
        public array $templateCandidates,
    ) {
    }

    public function primaryProviderKey(): string
    {
        return $this->providerKeys[0] ?? $this->resource.'.'.$this->operation;
    }

    public function identifierField(): string
    {
        if (null !== $this->itemField) {
            return $this->itemIdentifierField();
        }

        if (null !== $this->subjectField) {
            return $this->subjectIdentifierField();
        }

        return 'slug';
    }

    public function identifierValue(): string|int|null
    {
        return $this->itemValue ?? $this->subjectValue;
    }

    public function subjectIdentifierField(): string
    {
        if (null === $this->subjectField) {
            return 'subject';
        }

        return $this->normalizeIdentifierField($this->subjectField);
    }

    public function itemIdentifierField(): string
    {
        if (null === $this->itemField) {
            return 'item';
        }

        return $this->normalizeIdentifierField($this->itemField);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'resource' => $this->resource,
            'resourcePath' => $this->resourcePath,
            'operation' => $this->operation,
            'view' => $this->view,
            'surfacePath' => $this->surfacePath,
            'subjectField' => $this->subjectField,
            'subjectValue' => $this->subjectValue,
            'itemField' => $this->itemField,
            'itemValue' => $this->itemValue,
            'identifierField' => $this->identifierField(),
            'identifierValue' => $this->identifierValue(),
            'routeName' => $this->routeName,
            'routeTemplate' => $this->routeTemplate,
            'routeParameters' => $this->routeParameters,
            'providerKeys' => $this->providerKeys,
            'templateCandidates' => $this->templateCandidates,
        ];
    }

    private function normalizeIdentifierField(string $field): string
    {
        $lower = strtolower($field);
        if ('id' === $lower || str_ends_with($lower, 'id')) {
            return 'id';
        }

        if ('slug' === $lower || str_ends_with($lower, 'slug')) {
            return 'slug';
        }

        return $field;
    }
}
