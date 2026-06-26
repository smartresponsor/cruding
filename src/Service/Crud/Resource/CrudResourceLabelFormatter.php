<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

final class CrudResourceLabelFormatter
{
    public function humanize(string $value): string
    {
        $text = str_replace(['_', '-', '/'], ' ', $value);
        $text = preg_replace('/\s+/', ' ', $text) ?: $value;

        return ucwords(trim($text));
    }

    public function shortClass(object $object): string
    {
        $class = $object::class;
        $position = strrpos($class, '\\');

        return false === $position ? $class : substr($class, $position + 1);
    }
}
