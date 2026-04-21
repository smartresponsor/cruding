<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Api\Crud;

use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudApiInputHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudApiResponderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudMutationGuardInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CrudApiUpdateController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudObjectFinderInterface $objectFinder,
        private readonly CrudAccessContextBuilderInterface $accessContextBuilder,
        private readonly CrudMutationGuardInterface $mutationGuard,
        private readonly CrudApiInputHandlerInterface $apiInputHandler,
        private readonly CrudFormHandlerInterface $formHandler,
        private readonly CrudApiResponderInterface $apiResponder,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $context = $this->contextResolver->resolve($request);
        $object = $this->objectFinder->findOne($context);
        $access = $this->accessContextBuilder->build($context, $object);
        $this->mutationGuard->assertCanEdit($access);

        if (null === $context->formTypeClass) {
            throw new NotFoundHttpException(sprintf('Form type for "%s" could not be resolved.', $context->resourcePath));
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
