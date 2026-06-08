<?php

declare(strict_types=1);

namespace App\Cruding\Service\Surface;

use App\Cruding\Dto\Surface\CrudRouteMapEntry;
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
                $name = $matches[1][$index][0];
                $pattern .= $this->placeholderRegex($name);
                $offset = $position + strlen($placeholder);
            }
        }
        $pattern .= preg_quote(substr($template, $offset), '#');

        return '#^'.$pattern.'$#';
    }

    private function placeholderRegex(string $name): string
    {
        $lower = strtolower($name);
        if ('id' === $lower || str_ends_with($lower, 'id')) {
            return '\d+';
        }

        if ('slug' === $lower || str_ends_with($lower, 'slug')) {
            return '[A-Za-z0-9][A-Za-z0-9_-]*';
        }

        return '[^/]+';
    }
}
