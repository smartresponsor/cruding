<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Api\Crud;

use App\Cruding\ServiceInterface\Crud\CrudApiInputHandlerInterface;
use App\Cruding\ServiceInterface\Crud\CrudApiResponderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class CrudApiCreateController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudApiInputHandlerInterface $apiInputHandler,
        private readonly CrudFormHandlerInterface $formHandler,
        private readonly CrudApiResponderInterface $apiResponder,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $context = $this->contextResolver->resolve($request);
        $entityClass = $context->entityClass;
        $object = new $entityClass();

        if (null === $context->formTypeClass) {
            throw new NotFoundHttpException(sprintf('Form type for "%s" could not be resolved.', $context->resourcePath));
        }

        $form = $this->apiInputHandler->submit($context->formTypeClass, $object, $request, true);
        if (!$form->isSubmitted() || !$form->isValid()) {
            return $this->apiResponder->validationError($context, $form);
        }

        $this->formHandler->persist($object);

        return $this->apiResponder->item($context, $object, JsonResponse::HTTP_CREATED);
    }
}
