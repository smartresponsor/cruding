<?php

declare(strict_types=1);

namespace App\Cruding\Service;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
/**
 * Provides the passive crud service responsibility within the Cruding component.
 */
final class CrudPassiveService extends CrudAbstractService
{
    public function __construct(
        private object $service,
    ) {
    }

    /**      * Executes the raw service operation.      */
    public function rawService(): object
    {
        return $this->service;
    }
}
