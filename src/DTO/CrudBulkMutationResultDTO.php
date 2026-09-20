<?php

declare(strict_types=1);

namespace App\Cruding\DTO;

/**
 * Carries structured bulk mutation execution results.
 */
final readonly class CrudBulkMutationResultDTO
{
    /**
     * @param list<array{error: string, position: int}> $failures
     */
    public function __construct(
        public string $action,
        public string $dataScope,
        public int $attempted,
        public int $succeeded,
        public int $failed,
        public array $failures = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'scope' => $this->dataScope,
            'attempted' => $this->attempted,
            'succeeded' => $this->succeeded,
            'failed' => $this->failed,
            'failures' => $this->failures,
        ];
    }
}
