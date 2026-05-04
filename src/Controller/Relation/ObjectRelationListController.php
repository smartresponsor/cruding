<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Relation;

use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\Relation\ObjectRelationContextResolverInterface;
use App\Cruding\ServiceInterface\Relation\ObjectRelationManagerInterface;
use App\Cruding\ServiceInterface\Relation\ObjectRelationResponderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ObjectRelationListController extends AbstractController
{
    public function __construct(
        private readonly ObjectRelationContextResolverInterface $contextResolver,
        private readonly CrudObjectFinderInterface $objectFinder,
        private readonly CrudAccessContextBuilderInterface $accessContextBuilder,
        private readonly ObjectRelationManagerInterface $relationManager,
        private readonly ObjectRelationResponderInterface $responder,
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
        if (!$access->canView) {
            throw $this->createAccessDeniedException('You are not allowed to view this object relations.');
        }
        $relations = $this->relationManager->list($context, $subject);

        return $this->responder->htmlList($context, $subject, $relations);
    }
}
