<?php

declare(strict_types=1);

namespace App\Cruding\Service\Operation;

use App\Cruding\DTO\CrudMutationLifecycleContextDTO;
use App\Cruding\Factory\CrudNotFoundResponseFactory;
use App\Cruding\Responder\CrudEditResponder;
use App\Cruding\Runner\CrudServiceRunner;
use App\Cruding\Service\CrudMutationLifecycleDispatcher;
use App\Cruding\ServiceInterface\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\CrudMutationGuardInterface;
use App\Cruding\ServiceInterface\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Operation\CrudEditOperationInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the edit operation responsibility within the Cruding component.
 */
final readonly class CrudEditOperation implements CrudEditOperationInterface
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CrudObjectFinderInterface $objectFinder,
        private CrudFormHandlerInterface $formHandler,
        private CrudAccessContextBuilderInterface $accessContextBuilder,
        private CrudMutationGuardInterface $mutationGuard,
        private CrudNotFoundResponseFactory $notFoundResponseFactory,
        private CrudServiceRunner $entrypointRunner,
        private CrudMutationLifecycleDispatcher $mutationLifecycleDispatcher,
        private CrudEditResponder $responder,
    ) {
    }

    /**      * Handles the operation represented by this service.      */
    public function handle(Request $request): Response|CrudResourceContract
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->notFoundResponseFactory->create($request, 'crud_context_not_found');
        }

        $object = $this->objectFinder->findOne($context);
        if (null === $object) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
        }

        $access = $this->accessContextBuilder->build($context, $object);
        $this->mutationGuard->assertCanEdit($access);

        $entrypointResult = $this->entrypointRunner->tryRun($request, $context, $object);
        if (null !== $entrypointResult) {
            return $entrypointResult;
        }

        if (null === $context->formTypeClass) {
            return $this->notFoundResponseFactory->create($request, 'crud_resource_not_found');
        }

        $form = $this->formHandler->createAndHandle($context->formTypeClass, $object, $request);
        if ($form->isSubmitted() && $form->isValid()) {
            $lifecycleContext = new CrudMutationLifecycleContextDTO($context, $object, $request, 'update');
            $this->mutationLifecycleDispatcher->execute(
                $lifecycleContext,
                function () use ($object): void {
                    $this->formHandler->flush($object);
                },
            );

            return $this->responder->redirectToShow($context);
        }

        return $this->responder->resource($context, $object, $form->createView());
    }
}
