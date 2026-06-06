<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Operation;

use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudApiInputHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudApiResponderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudMutationGuardInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Crud\Operation\CrudApiUpdateOperationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class CrudApiUpdateOperation implements CrudApiUpdateOperationInterface
{
    public function __construct(
        private CrudContextResolverInterface $contextResolver,
        private CrudObjectFinderInterface $objectFinder,
        private CrudAccessContextBuilderInterface $accessContextBuilder,
        private CrudMutationGuardInterface $mutationGuard,
        private CrudApiInputHandlerInterface $apiInputHandler,
        private CrudFormHandlerInterface $formHandler,
        private CrudApiResponderInterface $apiResponder,
    ) {
    }

    public function handle(Request $request): Response
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->apiResponder->notFound((string) $request->attributes->get('resourcePath', ''));
        }

        $object = $this->objectFinder->findOne($context);
        if (null === $object) {
            return $this->apiResponder->notFound($context->resourcePath, sprintf(
                'Object for resource "%s" was not found by %s "%s".',
                $context->resourcePath,
                $context->identifierField,
                (string) $context->identifierValue,
            ));
        }

        $access = $this->accessContextBuilder->build($context, $object);
        $this->mutationGuard->assertCanEdit($access);

        if (null === $context->formTypeClass) {
            return $this->apiResponder->notFound($context->resourcePath, sprintf('Form type for "%s" could not be resolved.', $context->resourcePath));
        }

        $clearMissing = 'PATCH' !== strtoupper($request->getMethod());
        $form = $this->apiInputHandler->submit($context->formTypeClass, $object, $request, $clearMissing);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->apiResponder->validationError($context, $form);
        }

        $this->formHandler->flush($object);

        return $this->apiResponder->item($context, $object);
    }
}
