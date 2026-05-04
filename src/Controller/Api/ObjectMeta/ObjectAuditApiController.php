<?php

declare(strict_types=1);

namespace App\Cruding\Controller\Api\ObjectMeta;

use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudApiResponderInterface;
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
        private readonly CrudApiResponderInterface $apiResponder,
        private readonly ObjectAuditExtractorInterface $auditExtractor,
    ) {
    }

    public function __invoke(Request $request): JsonResponse
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
