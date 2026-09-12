<?php

declare(strict_types=1);

namespace App\Cruding\Service\Resource;

/**
 * Provides the resource label formatter responsibility within the Cruding component.
 */
final class CrudResourceLabelFormatter
{
    /**      * Executes the humanize operation.      */
    public function humanize(string $value): string
    {
        $text = str_replace(['_', '-', '/'], ' ', $value);
        $text = preg_replace('/\s+/', ' ', $text) ?: $value;

        return ucwords(trim($text));
    }

    /**      * Executes the short class operation.      */
    public function shortClass(object $object): string
    {
        $class = $object::class;
        $position = strrpos($class, '\\');

        return false === $position ? $class : substr($class, $position + 1);
    }
}
