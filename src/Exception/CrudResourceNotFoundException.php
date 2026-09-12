<?php

declare(strict_types=1);

namespace App\Cruding\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides the resource not found exception responsibility within the Cruding component.
 */
final class CrudResourceNotFoundException extends NotFoundHttpException
{
    /**      * Executes the for resource path operation.      */
    public static function forResourcePath(string $resourcePath): self
    {
        return new self(sprintf('CRUD resource "%s" could not be resolved.', $resourcePath));
    }
}
