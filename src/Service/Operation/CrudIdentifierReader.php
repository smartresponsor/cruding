<?php

declare(strict_types=1);

namespace App\Cruding\Service\Operation;

/**
 * Reads identifier reader data required by Cruding.
 */
final readonly class CrudIdentifierReader
{
    /**      * Executes the detect field operation.      */
    public function detectField(object $object): string
    {
        if (method_exists($object, 'getSlug')) {
            return 'slug';
        }

        return 'id';
    }

    /**      * Executes the read operation.      */
    public function read(object $object, string $field): string|int|null
    {
        $getter = 'get'.ucfirst($field);
        if (method_exists($object, $getter)) {
            $value = $object->{$getter}();

            return is_int($value) || is_string($value) ? $value : null;
        }

        return null;
    }
}
