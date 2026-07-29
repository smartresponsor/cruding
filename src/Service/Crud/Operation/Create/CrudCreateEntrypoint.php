<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation\Create;

use App\Cruding\Runner\Crud\CrudServiceRunner;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudCreateEntrypoint
{
    public function __construct(
        private CrudServiceRunner $entrypointRunner,
    ) {
    }

    public function tryRun(Request $request, CrudCreateWorkItem $workItem): Response|CrudResourceContract|null
    {
        return $this->entrypointRunner->tryRun($request, $workItem->context, $workItem->object);
    }
}
