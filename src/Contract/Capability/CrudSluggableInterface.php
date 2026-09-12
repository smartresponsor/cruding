<?php

declare(strict_types=1);

namespace App\Cruding\Contract\Capability;

/**
 * Defines the contract for sluggable within the Cruding component.
 */
interface CrudSluggableInterface
{
    /**      * Returns slug.      */
    public function getSlug(): string;
}
