<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Resource\CrudRouteMapEntry;
use App\Cruding\Service\Crud\Resource\CrudRouteMapMatcher as ResourceCrudRouteMapMatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * Backward-compatible facade for the canonical resource-scoped route-map matcher.
 *
 * New runtime wiring must depend on ResourceCrudRouteMapMatcher directly. This facade
 * intentionally contains no matching logic so the route-map algorithm has one owner.
 */
final readonly class CrudRouteMapMatcher
{
    public function __construct(
        private ResourceCrudRouteMapMatcher $matcher,
    ) {
    }

    public function match(Request $request): ?CrudRouteMapEntry
    {
        return $this->matcher->match($request);
    }

    /**
     * @return list<CrudRouteMapEntry>
     */
    public function entryList(): array
    {
        return $this->matcher->entryList();
    }
}
