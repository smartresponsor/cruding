<?php

declare(strict_types=1);

namespace App\Cruding\Exception\Crud;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CrudResourceNotFoundException extends NotFoundHttpException
{
    public static function forResourcePath(string $resourcePath): self
    {
        return new self(sprintf('CRUD resource "%s" could not be resolved.', $resourcePath));
    }
}
