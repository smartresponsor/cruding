<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Capability\CapabilityMatch;
use App\Cruding\Dto\Capability\CapabilityProfile;
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

    public function profile(object|string $subject): CapabilityProfile
    {
        $matches = [];
        foreach (array_keys($this->capabilityMap) as $capability) {
            $matches[$capability] = $this->match($capability, $subject);
        }

        return new CapabilityProfile($this->resolveClassName($subject), $matches);
    }

    public function supports(string $capability, object|string $subject): bool
    {
        return $this->match($capability, $subject)->supported;
    }

    public function match(string $capability, object|string $subject): CapabilityMatch
    {
        $config = $this->capabilityMap[$capability] ?? [];
        $reflection = new \ReflectionClass($this->resolveClassName($subject));

        foreach (($config['interfaces'] ?? []) as $interfaceName) {
            if ($reflection->implementsInterface($interfaceName)) {
                return new CapabilityMatch($capability, true, 'explicit_interface', null, null, $interfaceName);
            }
        }

        foreach (($config['methods_any'] ?? []) as $methodName) {
            if ($reflection->hasMethod($methodName)) {
                return new CapabilityMatch($capability, true, 'alias_method', $methodName, 'method');
            }
        }

        foreach (($config['properties_any'] ?? []) as $propertyName) {
            if ($reflection->hasProperty($propertyName)) {
                return new CapabilityMatch($capability, true, 'alias_property', $propertyName, 'property');
            }
        }

        return new CapabilityMatch($capability, false);
    }

    private function resolveClassName(object|string $subject): string
    {
        return is_object($subject) ? $subject::class : $subject;
    }
}
