<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Resource\CrudRouteMapEntry;
use App\Cruding\Service\Crud\Resource\CrudRouteMapLoader;
use Symfony\Component\HttpFoundation\Request;

/**
 * Matches the current request path against host route-map entries.
 */
final class CrudRouteMapMatcher
{
    /** @var list<CrudRouteMapEntry>|null */
    private ?array $entries = null;

    public function __construct(
        private readonly CrudRouteMapLoader $loader,
    ) {
    }

    public function match(Request $request): ?CrudRouteMapEntry
    {
        $path = rtrim($request->getPathInfo(), '/') ?: '/';
        foreach ($this->entryList() as $entry) {
            if (preg_match($this->regex($entry->path), $path)) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @return list<CrudRouteMapEntry>
     */
    public function entryList(): array
    {
        return $this->entries ??= $this->loader->entries();
    }

    private function regex(string $template): string
    {
        $template = rtrim($template, '/') ?: '/';
        $pattern = '';
        $offset = 0;
        if (preg_match_all('/\{([A-Za-z_][A-Za-z0-9_]*)\}/', $template, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                [$placeholder, $position] = $match;
                $pattern .= preg_quote(substr($template, $offset, $position - $offset), '#');
                $nameEntity = $matches[1][$index][0];
                $pattern .= $this->placeholderRegex($nameEntity);
                $offset = $position + strlen($placeholder);
            }
        }
        $pattern .= preg_quote(substr($template, $offset), '#');

        return '#^'.$pattern.'$#';
    }

    private function placeholderRegex(string $nameEntity): string
    {
        $lower = strtolower($nameEntity);
        if ('id' === $lower || str_ends_with($lower, 'id')) {
            return '\\d+';
        }

        if ('slug' === $lower || str_ends_with($lower, 'slug')) {
            return '[A-Za-z0-9][A-Za-z0-9_-]*';
        }

        return '[^/]+';
    }
}
