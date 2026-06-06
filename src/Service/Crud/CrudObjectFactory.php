<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\ServiceInterface\Crud\CrudObjectFactoryInterface;

final readonly class CrudObjectFactory implements CrudObjectFactoryInterface
{
    /**
     * @param class-string $entityClass
     */
    public function create(string $entityClass): object
    {
        $reflection = new \ReflectionClass($entityClass);
        $constructor = $reflection->getConstructor();
        if (null === $constructor) {
            return $reflection->newInstance();
        }

        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            $type = $parameter->getType();
            if ($type instanceof \ReflectionNamedType) {
                if ($type->allowsNull()) {
                    $arguments[] = null;
                    continue;
                }

                $arguments[] = match ($type->getName()) {
                    'string' => '',
                    'int' => 0,
                    'float' => 0.0,
                    'bool' => false,
                    'array' => [],
                    default => null,
                };
                continue;
            }

            $arguments[] = null;
        }

        try {
            return $reflection->newInstanceArgs($arguments);
        } catch (\Throwable) {
            return $reflection->newInstanceWithoutConstructor();
        }
    }
}
