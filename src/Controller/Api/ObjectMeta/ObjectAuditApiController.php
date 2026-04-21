<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Api\ObjectMeta;

use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\ObjectMeta\ObjectAuditExtractorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ObjectAuditApiController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudObjectFinderInterface $objectFinder,
        private readonly CrudAccessContextBuilderInterface $accessContextBuilder,
        private readonly ObjectAuditExtractorInterface $auditExtractor,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $context = $this->contextResolver->resolve($request);
        $object = $this->objectFinder->findOne($context);
        $access = $this->accessContextBuilder->build($context, $object);
        if (!$access->canView) {
            throw $this->createAccessDeniedException('You are not allowed to view this object audit.');
        }

        return $this->json([
            'resourcePath' => $context->resourcePath,
            'slug' => $context->identifierValue,
            'audit' => $this->auditExtractor->extract($object)->toArray(),
        ]);
    }
}
