<?php

declare(strict_types=1);

namespace App\Cruding\Value\Api;

final class CrudApiResponseTitle
{
    public static function fromStatusCode(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            422 => 'Validation Failed',
            default => 'HTTP Error',
        };
    }
}
