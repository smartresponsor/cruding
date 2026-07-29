<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Crud\CrudMutationLifecycleContext;

interface CrudMutationLifecycleSubscriberInterface
{
    public function supports(CrudMutationLifecycleContext $context): bool;

    public function before(CrudMutationLifecycleContext $context): void;

    public function after(CrudMutationLifecycleContext $context): void;
}
