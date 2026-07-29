<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation\Create;

use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudCreateFlow
{
    public function __construct(
        private CrudCreateWorkItemInitializer $initializer,
        private CrudCreateEntrypoint $entrypoint,
        private CrudCreateFormProcessor $formProcessor,
        private CrudCreateViewBuilder $viewBuilder,
    ) {
    }

    public function handle(Request $request): Response|CrudResourceContract
    {
        $workItem = $this->initializer->initialize($request);
        if ($workItem instanceof Response) {
            return $workItem;
        }

        $entrypointResult = $this->entrypoint->tryRun($request, $workItem);
        if (null !== $entrypointResult) {
            return $entrypointResult;
        }

        $formResult = $this->formProcessor->process($request, $workItem);
        if ($formResult instanceof Response) {
            return $formResult;
        }

        return $this->viewBuilder->build($workItem, $formResult);
    }
}
