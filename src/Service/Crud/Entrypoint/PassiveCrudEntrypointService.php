<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Entrypoint;

final class PassiveCrudEntrypointService extends AbstractCrudEntrypointService
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
