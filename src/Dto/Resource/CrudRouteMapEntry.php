<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Resource;

/**
 * Canonical route-map entry loaded from host platform route registry files.
 */
final readonly class CrudRouteMapEntry
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(
        public string $nameEntity,
        public string $path,
        public ?string $parser = null,
        public ?string $routeKey = null,
        public ?string $object = null,
        public ?string $template = null,
        public ?string $resolver = null,
        public ?string $service = null,
        public ?string $type = null,
        public array $extra = [],
    ) {
    }

    public function canonicalKey(): string
    {
        return $this->routeKey ?? $this->nameEntity;
    }

    public function identifierResolver(): ?string
    {
        if (null !== $this->resolver && '' !== $this->resolver) {
            return $this->resolver;
        }

        if (str_contains($this->path, '{id}') || preg_match('/\{[A-Za-z0-9]+Id\}/', $this->path)) {
            return 'id';
        }

        if (str_contains($this->path, '{slug}') || preg_match('/\{[A-Za-z0-9]+Slug\}/', $this->path)) {
            return 'slug';
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'nameEntity' => $this->nameEntity,
            'path' => $this->path,
            'parser' => $this->parser,
            'routeKey' => $this->routeKey,
            'canonicalKey' => $this->canonicalKey(),
            'object' => $this->object,
            'template' => $this->template,
            'resolver' => $this->identifierResolver(),
            'service' => $this->service,
            'type' => $this->type,
            'extra' => [] !== $this->extra ? $this->extra : null,
        ], static fn (mixed $value): bool => null !== $value);
    }
}
