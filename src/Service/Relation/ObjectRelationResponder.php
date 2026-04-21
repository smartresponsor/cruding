<?php

declare(strict_types=1);

namespace App\Cruding\Service\Relation;

use App\Cruding\Dto\Relation\ObjectRelationContext;
use App\Cruding\ServiceInterface\Relation\ObjectRelationResponderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ObjectRelationResponder extends AbstractController implements ObjectRelationResponderInterface
{
    public function htmlList(ObjectRelationContext $context, object $subject, array $relations): Response
    {
        return $this->render('@Cruding/relation/list.html.twig', [
            'crud' => $context->crud,
            'relation' => $context,
            'subject' => $subject,
            'relations' => $relations,
        ]);
    }

    public function apiList(ObjectRelationContext $context, object $subject, array $relations): Response
    {
        return new JsonResponse([
            'resourcePath' => $context->crud->resourcePath,
            'subjectSlug' => $context->crud->identifierValue,
            'relationKind' => $context->relationKind,
            'count' => count($relations),
            'items' => array_map([$this, 'normalizeObject'], $relations),
        ]);
    }

    public function apiAttached(ObjectRelationContext $context, object $subject, object $relation): Response
    {
        return new JsonResponse([
            'resourcePath' => $context->crud->resourcePath,
            'subjectSlug' => $context->crud->identifierValue,
            'relationKind' => $context->relationKind,
            'status' => 'attached',
            'item' => $this->normalizeObject($relation),
        ], Response::HTTP_CREATED);
    }

    public function apiDetached(ObjectRelationContext $context, object $subject): Response
    {
        return new JsonResponse([
            'resourcePath' => $context->crud->resourcePath,
            'subjectSlug' => $context->crud->identifierValue,
            'relationKind' => $context->relationKind,
            'relatedSlug' => $context->relatedSlug,
            'status' => 'detached',
        ]);
    }

    private function normalizeObject(object $object): array
    {
        $result = ['class' => $object::class];
        foreach (['getId' => 'id', 'getSlug' => 'slug', 'getName' => 'name'] as $method => $key) {
            if (method_exists($object, $method)) {
                $value = $object->{$method}();
                if (is_scalar($value) || null === $value) {
                    $result[$key] = $value;
                }
            }
        }
        if (method_exists($object, '__toString')) {
            $result['label'] = (string) $object;
        }

        return $result;
    }
}
