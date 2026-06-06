<?php

declare(strict_types=1);

namespace App\Cruding\Service\Runtime;

use App\Cruding\Dto\Runtime\CrudRuntimeLock;

/**
 * Reads accepted runtime scope locks from config/kernel/runtime_scope.*lock.php.
 */
final readonly class CrudRuntimeLockReader
{
    public function __construct(
        private CrudRuntimeTokenNormalizer $normalizer,
        private string $projectDir,
        private string $appEnv,
        private string $lockGlob,
    ) {
    }

    public function read(): CrudRuntimeLock
    {
        $path = $this->resolveLockPath();
        if (null === $path) {
            return new CrudRuntimeLock(
                appEnv: $this->appEnv,
                path: null,
                found: false,
                scopeTokens: [],
                entityTokens: [],
                surfaceTokens: [],
                reservedTokens: [],
                packageNames: [],
            );
        }

        $payload = $this->loadArray($path);

        return new CrudRuntimeLock(
            appEnv: $this->appEnv,
            path: $path,
            found: true,
            scopeTokens: $this->readTokenList($payload, ['scope', 'runtime_scope', 'APP_RUNTIME_SCOPE', 'runtime.scope.components']),
            entityTokens: $this->readTokenList($payload, ['entity', 'runtime_entity', 'APP_RUNTIME_ENTITY', 'runtime.routing.entities']),
            surfaceTokens: $this->readTokenList($payload, ['surface_token', 'surface_tokens', 'runtime_surface_token', 'APP_RUNTIME_SURFACE_TOKEN', 'runtime.routing.surface_tokens']),
            reservedTokens: $this->readTokenList($payload, ['reserved', 'reserved_tokens', 'runtime_reserved', 'APP_RUNTIME_RESERVED', 'runtime.routing.reserved_roots']),
            packageNames: $this->readPackageNames($payload),
        );
    }

    private function resolveLockPath(): ?string
    {
        $candidates = [
            $this->projectDir.'/config/kernel/runtime_scope.'.$this->appEnv.'.lock.php',
            $this->projectDir.'/config/kernel/runtime_scope.lock.php',
        ];

        $globPattern = str_replace(['%env%', '{env}'], $this->appEnv, $this->lockGlob);
        if ('' !== trim($globPattern)) {
            $candidates[] = str_starts_with($globPattern, DIRECTORY_SEPARATOR)
                ? $globPattern
                : $this->projectDir.'/'.ltrim($globPattern, '/\\');
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        foreach (glob($this->projectDir.'/config/kernel/runtime_scope.*lock.php') ?: [] as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadArray(string $path): array
    {
        $payload = require $path;

        return \is_array($payload) ? $payload : [];
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string>         $keys
     *
     * @return list<string>
     */
    private function readTokenList(array $payload, array $keys): array
    {
        foreach ($keys as $key) {
            $value = $this->readPath($payload, $key);
            if (null === $value) {
                continue;
            }

            return $this->valueToTokenList($value);
        }

        return [];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readPath(array $payload, string $path): mixed
    {
        if (array_key_exists($path, $payload)) {
            return $payload[$path];
        }

        $current = $payload;
        foreach (explode('.', $path) as $segment) {
            if (!\is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * @return list<string>
     */
    private function valueToTokenList(mixed $value): array
    {
        if (\is_string($value)) {
            return $this->normalizer->csvToTokenList($value);
        }

        if (!\is_array($value)) {
            return [];
        }

        $tokens = [];
        foreach ($value as $item) {
            if (\is_string($item)) {
                $tokens[] = $item;
            }
        }

        return $this->normalizer->normalizeTokenList($tokens);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<string>
     */
    private function readPackageNames(array $payload): array
    {
        foreach (['packages', 'package_names', 'composer_packages', 'runtime.scope.packages'] as $key) {
            $value = $this->readPath($payload, $key);
            if (null === $value) {
                continue;
            }

            if (\is_string($value)) {
                return $this->normalizer->csvToTokenList($value);
            }

            if (!\is_array($value)) {
                return [];
            }

            $packages = [];
            foreach ($value as $package) {
                if (\is_string($package)) {
                    $packages[$package] = $package;
                }
            }

            return array_values($packages);
        }

        return [];
    }
}
