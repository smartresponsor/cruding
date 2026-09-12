<?php

declare(strict_types=1);

namespace App\Cruding\Contract\Capability;

/**
 * Defines the contract for identifiable within the Cruding component.
 */
interface CrudIdentifiableInterface
{
    /**      * Returns id.      */
    public function getId(): int|string|null;
}
