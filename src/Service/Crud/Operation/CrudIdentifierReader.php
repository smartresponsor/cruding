<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation;

final readonly class CrudIdentifierReader
{
    public function detectField(object $object): string
    {
        if (method_exists($object, 'getSlug')) {
            return 'slug';
        }

        return 'id';
    }

    public function read(object $object, string $field): string|int|null
    {
        $getter = 'get'.ucfirst($field);
        if (method_exists($object, $getter)) {
            $value = $object->{$getter}();

            return is_scalar($value) ? $value : null;
        }

        return null;
    }
}
