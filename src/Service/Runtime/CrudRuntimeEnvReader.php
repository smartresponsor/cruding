<?php

declare(strict_types=1);

namespace App\Cruding\Service\Runtime;

/**
 * Reads runtime token environment variables from server/env/getenv sources.
 */
final class CrudRuntimeEnvReader
{
    public function read(string $name): string
    {
        $serverValue = $_SERVER[$name] ?? null;
        if (is_string($serverValue)) {
            return $serverValue;
        }

        $envValue = $_ENV[$name] ?? null;
        if (is_string($envValue)) {
            return $envValue;
        }

        $getenvValue = getenv($name);

        return is_string($getenvValue) ? $getenvValue : '';
    }
}
