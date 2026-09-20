<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use App\Cruding\DTO\CrudAccessContextDTO;
use App\Cruding\DTO\CrudBulkMutationContextDTO;
use App\Cruding\DTO\CrudContextDTO;

/**
 * Defines a host-provided named backend bulk mutation.
 */
interface CrudBulkMutationHandlerInterface
{
    /** Indicates whether this handler owns the named bulk mutation for the resolved resource. */
    public function supports(string $action, CrudContextDTO $context): bool;

    /** @return list<string> */
    public function allowedDataScopes(): array;

    /** Returns the canonical default Collectioning data scope for this mutation. */
    public function defaultDataScope(): string;

    /** Returns the backend permission required before scope resolution and mutation. */
    public function permission(): ?string;

    /** Determines whether the current actor may mutate this concrete scoped object. */
    public function canMutate(CrudAccessContextDTO $access, object $object): bool;

    /**
     * Whether a failing row should be recorded and processing should continue.
     */
    public function continueOnFailure(): bool;

    /** Applies the named mutation to one authorized scoped object. */
    public function mutate(object $object, CrudBulkMutationContextDTO $context): void;
}
