<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Resource;

final class CrudResourceOperationResolver
{
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

    public function defaultView(string $operation): string
    {
        return in_array($operation, ['new', 'edit'], true) ? 'form' : ('show' === $operation ? 'detail' : 'table');
    }

    public function workbenchMode(string $operation): string
    {
        return in_array($operation, ['new', 'edit'], true) ? 'form' : ('show' === $operation ? 'detail' : 'collection');
    }
}
