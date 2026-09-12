<?php

declare(strict_types=1);

namespace App\Cruding\Service\Operation;

use App\Cruding\DTO\CrudMutationLifecycleContextDTO;
use App\Cruding\Service\CrudMutationLifecycleDispatcher;
use App\Cruding\ServiceInterface\CrudApiInputHandlerInterface;
use App\Cruding\ServiceInterface\CrudApiResponderInterface;
use App\Cruding\ServiceInterface\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\CrudObjectFactoryInterface;
use App\Cruding\ServiceInterface\Operation\CrudApiCreateOperationInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the api create operation responsibility within the Cruding component.
 */
final readonly class CrudApiCreateOperation implements CrudApiCreateOperationInterface
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CrudApiInputHandlerInterface $apiInputHandler,
        private CrudFormHandlerInterface $formHandler,
        private CrudApiResponderInterface $apiResponder,
        private CrudObjectFactoryInterface $objectFactory,
        private CrudMutationLifecycleDispatcher $mutationLifecycleDispatcher,
    ) {
    }

    /**      * Handles the operation represented by this service.      */
    public function handle(Request $request): Response
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            $resourcePath = $request->attributes->get('resourcePath', '');

            return $this->apiResponder->notFound(is_scalar($resourcePath) ? (string) $resourcePath : '');
        }

        $entityClass = $context->entityClass;
        if ('' === $entityClass) {
            return $this->apiResponder->notFound($context->resourcePath, 'Entity class could not be resolved.');
        }
        /** @var class-string $entityClass */
        $object = $this->objectFactory->create($entityClass);

        if (null === $context->formTypeClass) {
            return $this->apiResponder->notFound($context->resourcePath, sprintf('Form type for "%s" could not be resolved.', $context->resourcePath));
        }

        $form = $this->apiInputHandler->submit($context->formTypeClass, $object, $request, true);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->apiResponder->validationError($context, $form);
        }

        $lifecycleContext = new CrudMutationLifecycleContextDTO($context, $object, $request, 'create');
        $this->mutationLifecycleDispatcher->execute(
            $lifecycleContext,
            function () use ($object): void {
                $this->formHandler->persist($object);
            },
        );

        return $this->apiResponder->item($context, $object, JsonResponse::HTTP_CREATED);
    }
}
