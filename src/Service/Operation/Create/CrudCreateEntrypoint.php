<?php

declare(strict_types=1);

namespace App\Cruding\Service\Operation\Create;

use App\Cruding\Runner\CrudServiceRunner;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the create entrypoint responsibility within the Cruding component.
 */
final readonly class CrudCreateEntrypoint
{
    public function __construct(
        private CrudServiceRunner $entrypointRunner,
    ) {
    }

    /**      * Attempts to run the selected Cruding workflow without forcing a result.      */
    public function tryRun(Request $request, CrudCreateWorkItem $workItem): Response|CrudResourceContract|null
    {
        return $this->entrypointRunner->tryRun($request, $workItem->context, $workItem->object);
    }
}
