<?php

declare(strict_types=1);

namespace App\Cruding\Service\Runtime;

/**
 * Reads runtime entity reader data required by Cruding.
 */
final readonly class CrudRuntimeEntityReader
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
