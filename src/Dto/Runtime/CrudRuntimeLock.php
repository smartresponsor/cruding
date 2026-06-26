<?php

declare(strict_types=1);

namespace App\Cruding\Dto\Runtime;

/**
 * Runtime scope lock data loaded from config/kernel/runtime_scope.*lock.php.
 */
final readonly class CrudRuntimeLock
{
    /**
     * @param list<string> $scopeTokens
     * @param list<string> $entityTokens
     * @param list<string> $viewTokens
     * @param list<string> $reservedTokens
     * @param list<string> $packageNames
     */
    public function __construct(
        public string $appEnv,
        public ?string $path,
        public bool $found,
        public array $scopeTokens,
        public array $entityTokens,
        public array $viewTokens,
        public array $reservedTokens,
        public array $packageNames,
    ) {
    }
}
