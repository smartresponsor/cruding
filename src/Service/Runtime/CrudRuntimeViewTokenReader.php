<?php

declare(strict_types=1);

namespace App\Cruding\Service\Runtime;

/**
 * Reads runtime view token reader data required by Cruding.
 */
final readonly class CrudRuntimeViewTokenReader
{
    public function __construct(
        private CrudRuntimeEnvReader $envReader,
        private CrudRuntimeTokenNormalizer $normalizer,
        private string $envName,
    ) {
    }

    /**
     * @return list<string>
     */
    public function read(): array
    {
        return $this->normalizer->csvToTokenList($this->envReader->read($this->envName));
    }
}
