<?php

declare(strict_types=1);

namespace App\Cruding\Resolver\Crud;

use App\Cruding\Dto\Capability\CrudCapabilityMatch;
use App\Cruding\Dto\Capability\CrudCapabilityProfile;
use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\ServiceInterface\Crud\CrudCapabilityResolverInterface;

final readonly class CrudCapabilityResolver implements CrudCapabilityResolverInterface
{
    /** @param array<string, array<string, list<string>>> $capabilityMap */
    public function __construct(private array $capabilityMap = [])
    {
    }

    public function resolve(CrudContext $context, ?object $object = null): array
    {
        $subject = null !== $object ? $object : $context->entityClass;

        return [
            'supportsSlug' => $this->supports('sluggable', $subject),
            'supportsId' => $this->supports('identifiable', $subject),
        ];
    }

    public function profile(object|string $subject): CrudCapabilityProfile
    {
        $matches = [];
        foreach (array_keys($this->capabilityMap) as $capability) {
            $matches[$capability] = $this->match($capability, $subject);
        }

        return new CrudCapabilityProfile($this->resolveClassName($subject), $matches);
    }

    public function supports(string $capability, object|string $subject): bool
    {
        return $this->match($capability, $subject)->supported;
    }

    public function match(string $capability, object|string $subject): CrudCapabilityMatch
    {
        $config = $this->capabilityMap[$capability] ?? [];
        $className = $this->resolveClassName($subject);

        if (!class_exists($className) && !interface_exists($className)) {
            return new CrudCapabilityMatch($capability, false);
        }

        $reflection = new \ReflectionClass($className);

        foreach (($config['interfaces'] ?? []) as $interfaceName) {
            if ($reflection->implementsInterface($interfaceName)) {
                return new CrudCapabilityMatch($capability, true, 'explicit_interface', null, null, $interfaceName);
            }
        }

        foreach (($config['methods_any'] ?? []) as $methodName) {
            if ($reflection->hasMethod($methodName)) {
                return new CrudCapabilityMatch($capability, true, 'alias_method', $methodName, 'method');
            }
        }

        foreach (($config['properties_any'] ?? []) as $propertyName) {
            if ($reflection->hasProperty($propertyName)) {
                return new CrudCapabilityMatch($capability, true, 'alias_property', $propertyName, 'property');
            }
        }

        return new CrudCapabilityMatch($capability, false);
    }

    private function resolveClassName(object|string $subject): string
    {
        return is_object($subject) ? $subject::class : $subject;
    }
}
