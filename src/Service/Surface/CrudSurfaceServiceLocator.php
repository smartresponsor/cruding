<?php

declare(strict_types=1);

namespace App\Cruding\Service\Surface;

use Psr\Container\ContainerInterface;

/**
 * Holds host App\Service\Http\* services collected at compile time.
 */
final readonly class CrudSurfaceServiceLocator
{
    public function __construct(
        private ContainerInterface $locator,
    ) {
    }

    public function has(string $serviceClass): bool
    {
        return $this->locator->has($serviceClass);
    }

    public function get(string $serviceClass): object
    {
        $service = $this->locator->get($serviceClass);
        if (!is_object($service)) {
            throw new \RuntimeException(sprintf('Resolved surface service "%s" is not an object.', $serviceClass));
        }

        return $service;
    }

    /**
     * @return list<string>
     */
    public function serviceIds(): array
    {
        if (!method_exists($this->locator, 'getProvidedServices')) {
            return [];
        }

        /** @var array<string, string> $provided */
        $provided = $this->locator->getProvidedServices();
        $ids = array_keys($provided);
        sort($ids);

        return $ids;
    }
}
