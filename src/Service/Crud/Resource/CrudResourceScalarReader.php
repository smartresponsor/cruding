<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

final class CrudResourceScalarReader
{
    /**
     * @param list<string> $readers
     */
    public function read(object $object, array $readers): string|int|float|bool|null
    {
        foreach ($readers as $reader) {
            if (method_exists($object, $reader)) {
                try {
                    $value = $object->{$reader}();
                } catch (\Throwable) {
                    continue;
                }

                return is_scalar($value) ? $value : null;
            }

            if (property_exists($object, $reader)) {
                try {
                    $reflectionProperty = new \ReflectionProperty($object, $reader);
                    if (!$reflectionProperty->isPublic()) {
                        continue;
                    }

                    $value = $reflectionProperty->getValue($object);
                } catch (\Throwable) {
                    continue;
                }

                return is_scalar($value) ? $value : null;
            }
        }

        return null;
    }
}
