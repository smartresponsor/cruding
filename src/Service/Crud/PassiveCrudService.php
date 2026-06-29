<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
final class PassiveCrudService extends AbstractCrudService
{
    public function __construct(
        private object $service,
    ) {
    }

    public function rawService(): object
    {
        return $this->service;
    }
}
