<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Api\Relation;

use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudMutationGuardInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Relation\ObjectRelationContextResolverInterface;
use App\Cruding\ServiceInterface\Relation\ObjectRelationManagerInterface;
use App\Cruding\ServiceInterface\Relation\ObjectRelationResponderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ObjectRelationApiDetachController extends AbstractController
{
    public function __construct(
        private readonly ObjectRelationContextResolverInterface $contextResolver,
        private readonly CrudObjectFinderInterface $objectFinder,
        private readonly CrudAccessContextBuilderInterface $accessContextBuilder,
        private readonly CrudMutationGuardInterface $mutationGuard,
        private readonly ObjectRelationManagerInterface $relationManager,
        private readonly ObjectRelationResponderInterface $responder,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return $this->responder->notFound((string) $request->attributes->get('resourcePath', ''));
        }

        $subject = $this->objectFinder->findOne($context->crud);
        if (null === $subject) {
            return $this->responder->notFound($context->crud->resourcePath, sprintf(
                'Object for resource "%s" was not found by %s "%s".',
                $context->crud->resourcePath,
                $context->crud->identifierField,
                (string) $context->crud->identifierValue,
            ));
        }

        $access = $this->accessContextBuilder->build($context->crud, $subject);
        $this->mutationGuard->assertCanEdit($access);
        $this->relationManager->detach($context, $subject);

        return $this->responder->apiDetached($context, $subject);
    }
}
