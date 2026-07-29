<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation;

use App\Cruding\Dto\Crud\CrudCreateOwnerBindingContext;
use App\Cruding\Dto\Crud\CrudMutationLifecycleContext;
use App\Cruding\Service\Crud\CrudCreateOwnerBinderChain;
use App\Cruding\Service\Crud\CrudMutationLifecycleDispatcher;
use App\Cruding\ServiceInterface\Crud\CrudApiInputHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudApiResponderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFactoryInterface;
use App\Cruding\ServiceInterface\Crud\Operation\CrudApiCreateOperationInterface;
use Symfony\Bundle\SecurityBundle\Security;
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
        private Security $security,
        private CrudCreateOwnerBinderChain $createOwnerBinderChain,
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

        $this->bindActorOwner($context, $request, $object);
        $lifecycleContext = new CrudMutationLifecycleContext($context, $object, $request, 'create');
        $this->mutationLifecycleDispatcher->execute(
            $lifecycleContext,
            function () use ($object): void {
                $this->formHandler->persist($object);
            },
        );

        return $this->apiResponder->item($context, $object, JsonResponse::HTTP_CREATED);
    }

    private function bindActorOwner(\App\Cruding\Dto\Crud\CrudContext $context, Request $request, object $object): void
    {
        if ('my' !== $request->attributes->get('_crud_actor_scope')) {
            return;
        }

        $actor = $this->security->getUser();
        if (!is_object($actor)) {
            return;
        }

        $this->createOwnerBinderChain->bind(new CrudCreateOwnerBindingContext(
            crudContext: $context,
            object: $object,
            request: $request,
            actor: $actor,
        ));
    }
}
