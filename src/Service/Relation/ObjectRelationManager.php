<?php

declare(strict_types=1);

namespace App\Cruding\Service\Relation;

use App\Cruding\Dto\Relation\ObjectRelationContext;
use App\Cruding\ServiceInterface\Relation\ObjectRelationManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class ObjectRelationManager implements ObjectRelationManagerInterface
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    public function list(ObjectRelationContext $context, object $subject): array
    {
        $getter = $context->collectionGetter;
        if (!method_exists($subject, $getter)) {
            return [];
        }

        $value = $subject->{$getter}();
        if ($value instanceof \Traversable) {
            return array_values(iterator_to_array($value));
        }

        return is_array($value) ? array_values($value) : [];
    }

    public function attach(ObjectRelationContext $context, object $subject): object
    {
        if (null === $context->targetClass || null === $context->relatedSlug || '' === $context->relatedSlug) {
            throw new BadRequestHttpException('relatedSlug is required for attach operation.');
        }

        $related = $this->managerRegistry->getRepository($context->targetClass)->findOneBy([$context->targetIdentifierField => $context->relatedSlug]);
        if (null === $related) {
            throw new NotFoundHttpException(sprintf('Related %s "%s" was not found.', $context->relationKind, $context->relatedSlug));
        }

        if (!method_exists($subject, $context->addMethod)) {
            throw new BadRequestHttpException(sprintf('Subject does not support %s attach operation.', $context->relationKind));
        }

        $subject->{$context->addMethod}($related);
        $manager = $this->managerRegistry->getManagerForClass($context->crud->entityClass) ?? $this->managerRegistry->getManager();
        $manager->persist($subject);
        $manager->flush();

        return $related;
    }

    public function detach(ObjectRelationContext $context, object $subject): void
    {
        if (null === $context->targetClass || null === $context->relatedSlug || '' === $context->relatedSlug) {
            throw new BadRequestHttpException('relatedSlug is required for detach operation.');
        }

        $related = $this->managerRegistry->getRepository($context->targetClass)->findOneBy([$context->targetIdentifierField => $context->relatedSlug]);
        if (null === $related) {
            throw new NotFoundHttpException(sprintf('Related %s "%s" was not found.', $context->relationKind, $context->relatedSlug));
        }

        if (!method_exists($subject, $context->removeMethod)) {
            throw new BadRequestHttpException(sprintf('Subject does not support %s detach operation.', $context->relationKind));
        }

        $subject->{$context->removeMethod}($related);
        $manager = $this->managerRegistry->getManagerForClass($context->crud->entityClass) ?? $this->managerRegistry->getManager();
        $manager->persist($subject);
        $manager->flush();
    }
}
