<?php

declare(strict_types=1);

namespace App\Cruding\Service\Runtime;

/**
 * Reads APP_RUNTIME_VIEW_TOKEN-style View grammar tokens.
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
