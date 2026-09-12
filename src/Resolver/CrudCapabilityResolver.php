<?php

declare(strict_types=1);

namespace App\Cruding\Resolver;

use App\Cruding\DTO\Capability\CrudCapabilityMatchDTO;
use App\Cruding\DTO\Capability\CrudCapabilityProfileDTO;
use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\ServiceInterface\CrudCapabilityResolverInterface;

/**
 * Resolves capability resolver for Cruding request processing.
 */
final readonly class CrudCapabilityResolver implements CrudCapabilityResolverInterface
{
    /** @param array<string, array<string, list<string>>> $capabilityMap */
    public function __construct(private array $capabilityMap = [])
    {
    }

    /**      * Executes the resolve operation.      */
    public function resolve(CrudContextDTO $context, ?object $object = null): array
    {
        $subject = null !== $object ? $object : $context->entityClass;

        return [
            'supportsSlug' => $this->supports('sluggable', $subject),
            'supportsId' => $this->supports('identifiable', $subject),
        ];
    }

    /**      * Executes the profile operation.      */
    public function profile(object|string $subject): CrudCapabilityProfileDTO
    {
        $matches = [];
        foreach (array_keys($this->capabilityMap) as $capability) {
            $matches[$capability] = $this->match($capability, $subject);
        }

        return new CrudCapabilityProfileDTO($this->resolveClassName($subject), $matches);
    }

    /**      * Indicates whether this implementation supports the supplied context.      */
    public function supports(string $capability, object|string $subject): bool
    {
        return $this->match($capability, $subject)->supported;
    }

    /**      * Executes the match operation.      */
    public function match(string $capability, object|string $subject): CrudCapabilityMatchDTO
    {
        $config = $this->capabilityMap[$capability] ?? [];
        $className = $this->resolveClassName($subject);

        if (!class_exists($className) && !interface_exists($className)) {
            return new CrudCapabilityMatchDTO($capability, false);
        }

        $reflection = new \ReflectionClass($className);

        foreach (($config['interfaces'] ?? []) as $interfaceName) {
            if ($reflection->implementsInterface($interfaceName)) {
                return new CrudCapabilityMatchDTO($capability, true, 'explicit_interface', null, null, $interfaceName);
            }
        }

        foreach (($config['methods_any'] ?? []) as $methodName) {
            if ($reflection->hasMethod($methodName)) {
                return new CrudCapabilityMatchDTO($capability, true, 'alias_method', $methodName, 'method');
            }
        }

        foreach (($config['properties_any'] ?? []) as $propertyName) {
            if ($reflection->hasProperty($propertyName)) {
                return new CrudCapabilityMatchDTO($capability, true, 'alias_property', $propertyName, 'property');
            }
        }

        return new CrudCapabilityMatchDTO($capability, false);
    }

    private function resolveClassName(object|string $subject): string
    {
        return is_object($subject) ? $subject::class : $subject;
    }
}
