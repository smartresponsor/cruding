<?php

declare(strict_types=1);

namespace App\Cruding\Controller\ObjectMeta;

use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudContextResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudObjectFinderInterface;
use App\Cruding\ServiceInterface\ObjectMeta\ObjectAuditExtractorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ObjectAuditController extends AbstractController
{
    public function __construct(
        private readonly CrudContextResolverInterface $contextResolver,
        private readonly CrudObjectFinderInterface $objectFinder,
        private readonly CrudAccessContextBuilderInterface $accessContextBuilder,
        private readonly ObjectAuditExtractorInterface $auditExtractor,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $context = $this->contextResolver->tryResolve($request);
        if (null === $context) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $object = $this->objectFinder->findOne($context);
        if (null === $object) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $access = $this->accessContextBuilder->build($context, $object);
        if (!$access->canView) {
            throw $this->createAccessDeniedException('You are not allowed to view this object audit.');
        }

        return $this->render('@Cruding/object_meta/audit.html.twig', [
            'crud' => $context,
            'crud_access' => $access,
            'object' => $object,
            'audit' => $this->auditExtractor->extract($object),
        ]);
    }
}
