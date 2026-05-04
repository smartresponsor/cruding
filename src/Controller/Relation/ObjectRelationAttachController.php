<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Relation;

use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudMutationGuardInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Relation\ObjectRelationContextResolverInterface;
use App\Cruding\ServiceInterface\Relation\ObjectRelationManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ObjectRelationAttachController extends AbstractController
{
    public function __construct(
        private readonly ObjectRelationContextResolverInterface $contextResolver,
        private readonly CrudObjectFinderInterface $objectFinder,
        private readonly CrudAccessContextBuilderInterface $accessContextBuilder,
        private readonly CrudMutationGuardInterface $mutationGuard,
        private readonly ObjectRelationManagerInterface $relationManager,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $subject = $this->objectFinder->findOne($context->crud);
        if (null === $subject) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $access = $this->accessContextBuilder->build($context->crud, $subject);
        $this->mutationGuard->assertCanEdit($access);
        try {
            $this->relationManager->attach($context, $subject);
        } catch (\Symfony\Component\HttpKernel\Exception\BadRequestHttpException) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        return $this->redirect($request->headers->get('referer') ?: sprintf('/%s/%s/%s/', $context->crud->resourcePath, $context->relationKind, (string) $context->crud->identifierValue));
    }
}
