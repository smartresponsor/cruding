<?php

declare(strict_types=1);

namespace App\Cruding\Builder\Resource;

use App\Cruding\DTO\Resource\CrudResourceBlockDTO;
use App\Cruding\DTO\Resource\CrudResourceRequestDTO;
use App\Cruding\DTO\Resource\CrudRouteContextDTO;
use App\Cruding\ValueObject\Resource\CrudResourceContract;

/**
 * Builds resource payload builder values used by Cruding workflows.
 */
final class CrudResourcePayloadBuilder
{
    /** @var array<string, list<array{key: string, type: string, data: array<string, mixed>, meta: array<string, mixed>}>> */
    private array $locations = [];

    /** @var array<string, mixed> */
    private array $meta = [];

    private function __construct(
        private readonly CrudRouteContextDTO $routeContext,
        private readonly string $view,
    ) {
    }

    /**      * Executes the from request operation.      */
    public static function fromRequest(CrudResourceRequestDTO $request): self
    {
        return new self($request->routeContext, $request->routeContext->view);
    }

    /**      * Executes the from context operation.      */
    public static function fromContext(CrudRouteContextDTO $context): self
    {
        return new self($context, $context->view);
    }

    /**      * Executes the title operation.      */
    public function title(string $title): self
    {
        $this->meta['title'] = $title;

        return $this;
    }

    /**      * Executes the meta operation.      */
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
        return $this->viewBlock($location, new CrudResourceBlockDTO(
            key: $this->token($key) ?: 'block',
            type: $this->token($type ?? $key) ?: 'block',
            data: $data,
            meta: $meta,
        ));
    }

    /**      * Executes the view block operation.      */
    public function viewBlock(string $location, CrudResourceBlockDTO $block): self
    {
        $location = $this->location($location);
        if ('' === $location) {
            return $this;
        }

        $this->locations[$location] ??= [];
        $this->locations[$location][] = $block->toArray();

        return $this;
    }

    /**      * Executes the to contract operation.      */
    public function toContract(): CrudResourceContract
    {
        return CrudResourceContract::forResource(
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
