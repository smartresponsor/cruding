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
        $context = $this->contextResolver->resolve($request);
        $subject = $this->objectFinder->findOne($context->crud);
        $access = $this->accessContextBuilder->build($context->crud, $subject);
        $this->mutationGuard->assertCanEdit($access);
        $this->relationManager->attach($context, $subject);

        return $this->redirect($request->headers->get('referer') ?: sprintf('/%s/%s/%s/', $context->crud->resourcePath, $context->relationKind, (string) $context->crud->identifierValue));
    }
}
