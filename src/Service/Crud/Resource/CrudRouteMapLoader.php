<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

use App\Cruding\Dto\Resource\CrudRouteMapEntry;

/**
 * Loads one-line platform route-map entries from the host application.
 */
final readonly class CrudRouteMapLoader
{
    /** @var array<string, true> */
    private const CENTRAL_ROUTE_COMPONENTS = [
        'Cruding' => true,
        'Interfacing' => true,
        'Viewing' => true,
        'Vendoring' => true,
    ];

    public function __construct(
        private string $projectDir,
        private string $relativeDirectory = 'config/platform/routes',
    ) {
    }

    /**
     * @return list<CrudRouteMapEntry>
     */
    public function entries(): array
    {
        $entries = [];
        foreach ($this->routeRoots() as $directory) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
            foreach ($iterator as $file) {
                if (!$file instanceof \SplFileInfo || !$file->isFile() || 'yaml' !== strtolower($file->getExtension())) {
                    continue;
                }

                foreach ($this->entriesFromFile($file->getPathname()) as $entry) {
                    $entries[] = $entry;
                }
            }
        }

        return $entries;
    }

    /**
     * @return list<CrudRouteMapEntry>
     */
    private function entriesFromFile(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (!is_array($lines)) {
            return [];
        }

        $entries = [];
        foreach ($lines as $line) {
            $entry = $this->entryFromLine($line);
            if (null !== $entry) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    private function entryFromLine(string $line): ?CrudRouteMapEntry
    {
        $line = trim($line);
        if ('' === $line || str_starts_with($line, '#') || !str_contains($line, ':') || !str_contains($line, '{')) {
            return null;
        }

        [$nameEntity, $payload] = explode(':', $line, 2);
        $nameEntity = trim($nameEntity);
        if ('' === $nameEntity || str_contains($nameEntity, ' ')) {
            return null;
        }

        $payload = trim($payload);
        if (!str_starts_with($payload, '{') || !str_ends_with($payload, '}')) {
            return null;
        }

        $data = $this->parseInlineMap(substr($payload, 1, -1));
        $path = $this->string($data['path'] ?? null);
        if (null === $path || '' === $path) {
            return null;
        }

        return new CrudRouteMapEntry(
            nameEntity: $nameEntity,
            path: $path,
            parser: $this->string($data['parser'] ?? null),
            routeKey: $this->string($data['routeKey'] ?? null),
            object: $this->string($data['object'] ?? null),
            template: $this->string($data['template'] ?? null),
            resolver: $this->string($data['resolver'] ?? null),
            service: $this->string($data['service'] ?? null),
            type: $this->string($data['type'] ?? null),
            extra: array_diff_key($data, array_flip(['path', 'parser', 'routeKey', 'object', 'template', 'resolver', 'service', 'type'])),
        );
    }

    /**
     * @return array<string, string>
     */
    private function parseInlineMap(string $payload): array
    {
        $data = [];
        foreach ($this->splitTopLevel($payload) as $part) {
            if (!str_contains($part, ':')) {
                continue;
            }

            [$key, $value] = explode(':', $part, 2);
            $key = trim($key, " \t\n\r\0\x0B'\"");
            $value = trim($value, " \t\n\r\0\x0B'\"");
            if ('' !== $key && '' !== $value) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    private function splitTopLevel(string $payload): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $length = strlen($payload);

        for ($i = 0; $i < $length; ++$i) {
            $char = $payload[$i];
            if ('{' === $char || '[' === $char || '(' === $char) {
                ++$depth;
            } elseif ('}' === $char || ']' === $char || ')' === $char) {
                $depth = max(0, $depth - 1);
            }

            if (',' === $char && 0 === $depth) {
                $parts[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if ('' !== trim($current)) {
            $parts[] = trim($current);
        }

        return $parts;
    }

    private function string(mixed $value): ?string
    {
        return is_string($value) && '' !== trim($value) ? trim($value) : null;
    }

    /**
     * @return list<string>
     */
    private function routeRoots(): array
    {
        $roots = [];
        $local = $this->routeRoot($this->projectDir);
        if (null !== $local) {
            $roots[] = $local;
        }

        $baseDir = dirname(rtrim($this->projectDir, '/\\'));
        foreach (array_keys(self::CENTRAL_ROUTE_COMPONENTS) as $component) {
            $candidate = $baseDir.DIRECTORY_SEPARATOR.$component;
            $real = realpath($candidate);
            if (false === $real) {
                continue;
            }

            $root = $this->routeRoot($real);
            if (null !== $root) {
                $roots[] = $root;
            }
        }

        return array_values(array_unique($roots));
    }

    private function routeRoot(string $componentDir): ?string
    {
        $directory = rtrim($componentDir, '/\\').DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, trim($this->relativeDirectory, '/'));
        if (!is_dir($directory)) {
            return null;
        }

        return $directory;
    }
}
