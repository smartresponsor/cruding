<?php

declare(strict_types=1);

namespace App\Cruding\Service\Runtime;

/**
 * Reads runtime env reader data required by Cruding.
 */
final class CrudRuntimeEnvReader
{
    /**      * Executes the read operation.      */
    public function read(string $nameEntity): string
    {
        $serverValue = $_SERVER[$nameEntity] ?? null;
        if (is_string($serverValue)) {
            return $serverValue;
        }

        $envValue = $_ENV[$nameEntity] ?? null;
        if (is_string($envValue)) {
            return $envValue;
        }

        $getenvValue = getenv($nameEntity);

        return is_string($getenvValue) ? $getenvValue : '';
    }
}
