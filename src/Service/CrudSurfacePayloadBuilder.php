<?php

declare(strict_types=1);

namespace App\Cruding\Service;

use App\Cruding\Dto\Surface\CrudRouteContext;
use App\Cruding\Dto\Surface\CrudSurfaceBlock;
use App\Cruding\Dto\Surface\CrudSurfaceRequest;
use App\Cruding\Value\Surface\CrudSurfaceContract;

/**
 * Small producer-side helper for building location-based surface payloads.
 *
 * The builder keeps controller-facing payload shape stable while allowing each
 * producer to expose its own block set and block data volume per template.
 */
final class CrudSurfacePayloadBuilder
{
    /** @var array<string, list<array{key: string, type: string, data: array<string, mixed>, meta: array<string, mixed>}>> */
    private array $locations = [];

    /** @var array<string, mixed> */
    private array $meta = [];

    private function __construct(
        private readonly CrudRouteContext $routeContext,
        private readonly string $view,
    ) {
    }

    public static function fromRequest(CrudSurfaceRequest $request): self
    {
        return new self($request->routeContext, $request->routeContext->view);
    }

    public static function fromContext(CrudRouteContext $context): self
    {
        return new self($context, $context->view);
    }

    public function title(string $title): self
    {
        $this->meta['title'] = $title;

        return $this;
    }

    public function meta(string $key, mixed $value): self
    {
        $key = $this->token($key);
        if ('' === $key) {
            return $this;
        }

        $this->meta[$key] = $value;

        return $this;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     */
    public function block(string $location, string $key, array $data = [], ?string $type = null, array $meta = []): self
    {
        return $this->surfaceBlock($location, new CrudSurfaceBlock(
            key: $this->token($key) ?: 'block',
            type: $this->token($type ?? $key) ?: 'block',
            data: $data,
            meta: $meta,
        ));
    }

    public function surfaceBlock(string $location, CrudSurfaceBlock $block): self
    {
        $location = $this->location($location);
        if ('' === $location) {
            return $this;
        }

        $this->locations[$location] ??= [];
        $this->locations[$location][] = $block->toArray();

        return $this;
    }

    public function toContract(): CrudSurfaceContract
    {
        return CrudSurfaceContract::forSurface(
            view: $this->view,
            routeContext: $this->routeContext->toArray(),
            locations: $this->locations,
            meta: $this->meta,
        );
    }

    private function location(string $location): string
    {
        $location = trim($location);
        if ('' === $location) {
            return '';
        }

        $parts = array_filter(explode('.', str_replace('/', '.', $location)), static fn (string $part): bool => '' !== trim($part));
        $parts = array_map(fn (string $part): string => $this->token($part), $parts);
        $parts = array_filter($parts, static fn (string $part): bool => '' !== $part);

        return implode('.', $parts);
    }

    private function token(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/(?<!^)[A-Z]/', '-$0', $value) ?: $value;
        $value = strtolower(str_replace('_', '-', $value));
        $value = preg_replace('/[^a-z0-9-]+/', '-', $value) ?: $value;

        return trim(preg_replace('/-+/', '-', $value) ?: $value, '-');
    }
}
