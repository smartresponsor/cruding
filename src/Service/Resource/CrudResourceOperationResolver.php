<?php

declare(strict_types=1);

namespace App\Cruding\Service\Resource;

/**
 * Resolves resource operation resolver for Cruding request processing.
 */
final class CrudResourceOperationResolver
{
    /**      * Executes the resolve operation.      */
    public function resolve(string $explicitOperation, string $template): string
    {
        if ('' !== $explicitOperation) {
            return $explicitOperation;
        }

        return match (true) {
            str_contains($template, '/new.') => 'new',
            str_contains($template, '/edit.') => 'edit',
            str_contains($template, '/show.') => 'show',
            default => 'index',
        };
    }

    /**      * Executes the default view operation.      */
    public function defaultView(string $operation): string
    {
        return in_array($operation, ['new', 'edit'], true) ? 'form' : ('show' === $operation ? 'detail' : 'table');
    }

    /**      * Executes the workbench mode operation.      */
    public function workbenchMode(string $operation): string
    {
        return in_array($operation, ['new', 'edit'], true) ? 'form' : ('show' === $operation ? 'detail' : 'collection');
    }
}
