<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

use App\Cruding\Dto\Resource\CrudRouteContext;
use App\Cruding\ServiceInterface\Crud\Resource\CrudResourceProviderInterface;

final class CrudResourceProviderLocator
{
    /** @var array<string, CrudResourceProviderInterface>|null */
    private ?array $providerMap = null;

    /**
     * @param iterable<CrudResourceProviderInterface> $providers
     */
    public function __construct(
        private readonly iterable $providers = [],
    ) {
    }

    public function locate(CrudRouteContext $context): ?CrudResourceProviderInterface
    {
        $map = $this->providerMap();
        foreach ($context->providerKeys as $key) {
            if (isset($map[$key])) {
                return $map[$key];
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        $keys = array_keys($this->providerMap());
        sort($keys);

        return $keys;
    }

    /**
     * @return array<string, class-string<CrudResourceProviderInterface>>
     */
    public function entries(): array
    {
        $entries = [];
        foreach ($this->providerMap() as $key => $provider) {
            $entries[$key] = $provider::class;
        }
        ksort($entries);

        return $entries;
    }

    /**
     * @return array<string, CrudResourceProviderInterface>
     */
    private function providerMap(): array
    {
        if (null !== $this->providerMap) {
            return $this->providerMap;
        }

        $map = [];
        foreach ($this->providers as $provider) {
            foreach ($this->keysForProvider($provider) as $key) {
                $map[$key] = $provider;
            }
        }

        return $this->providerMap = $map;
    }

    /**
     * @return list<string>
     */
    private function keysForProvider(CrudResourceProviderInterface $provider): array
    {
        $shortName = $this->shortClass($provider::class);
        $base = preg_replace('/(viewProvider|Provider|view)$/', '', $shortName) ?: $shortName;
        $token = $this->classToken($base);
        if ('' === $token) {
            return [];
        }

        $key = str_replace('-', '.', $token);
        $keys = [$key];

        if (str_ends_with($key, '.index')) {
            $keys[] = substr($key, 0, -6);
        }

        return array_values(array_unique($keys));
    }

    private function classToken(string $className): string
    {
        $token = preg_replace('/(?<!^)[A-Z]/', '-$0', $className) ?: $className;
        $token = strtolower(str_replace('_', '-', $token));
        $token = preg_replace('/[^a-z0-9-]+/', '-', $token) ?: $token;

        return trim(preg_replace('/-+/', '-', $token) ?: $token, '-');
    }

    private function shortClass(string $class): string
    {
        $position = strrpos($class, '\\');

        return false === $position ? $class : substr($class, $position + 1);
    }
}
