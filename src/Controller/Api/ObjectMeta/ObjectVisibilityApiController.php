<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Api\ObjectMeta;

use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudMutationGuardInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\ObjectMeta\ObjectVisibilityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ObjectVisibilityApiController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudObjectFinderInterface $objectFinder,
        private readonly CrudAccessContextBuilderInterface $accessContextBuilder,
        private readonly CrudMutationGuardInterface $mutationGuard,
        private readonly ObjectVisibilityManagerInterface $visibilityManager,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $context = $this->contextResolver->resolve($request);
        $object = $this->objectFinder->findOne($context);
        $access = $this->accessContextBuilder->build($context, $object);
        $this->mutationGuard->assertCanEdit($access);

        $transition = (string) $request->attributes->get('transition');
        $state = $this->visibilityManager->apply($object, $transition);

        return $this->json([
            'resourcePath' => $context->resourcePath,
            'slug' => $context->identifierValue,
            'transition' => $transition,
            'visibility' => $state->toArray(),
        ]);
    }
}
