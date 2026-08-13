<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation;

use App\Cruding\Dto\Crud\CrudMutationLifecycleContext;
use App\Cruding\Service\Crud\CrudMutationLifecycleDispatcher;
use App\Cruding\ServiceInterface\Crud\CrudApiInputHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudApiResponderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFactoryInterface;
use App\Cruding\ServiceInterface\Crud\Operation\CrudApiCreateOperationInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    public function handle(Request $request): Response
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->apiResponder->notFound((string) $request->attributes->get('resourcePath', ''));
        }

        $object = $this->objectFactory->create($context->entityClass);

        if (null === $context->formTypeClass) {
            return $this->apiResponder->notFound($context->resourcePath, sprintf('Form type for "%s" could not be resolved.', $context->resourcePath));
        }

        $form = $this->apiInputHandler->submit($context->formTypeClass, $object, $request, true);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->apiResponder->validationError($context, $form);
        }

        $lifecycleContext = new CrudMutationLifecycleContext($context, $object, $request, 'create');
        $this->mutationLifecycleDispatcher->execute(
            $lifecycleContext,
            function () use ($object): void {
                $this->formHandler->persist($object);
            },
        );

        return $this->apiResponder->item($context, $object, JsonResponse::HTTP_CREATED);
    }
}
