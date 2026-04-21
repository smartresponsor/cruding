<?php

declare(strict_types=1);

namespace App\Cruding\Service\ObjectMeta;

use App\Cruding\Dto\ObjectMeta\ObjectVisibilityContext;
use App\Cruding\ServiceInterface\Crud\CrudFormHandlerInterface;
use App\Cruding\ServiceInterface\ObjectMeta\ObjectVisibilityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class ObjectVisibilityManager implements ObjectVisibilityManagerInterface
{
    /** @param array<string, array{writes?: array<string, bool>}> $visibilityTransitions */
    public function __construct(
        private readonly CrudFormHandlerInterface $formHandler,
        private readonly array $visibilityTransitions,
    ) {
    }

    public function inspect(object $object): ObjectVisibilityContext
    {
        return new ObjectVisibilityContext(
            $this->readBool($object, ['isVisible', 'getVisible', 'isEnabled', 'getEnabled', 'isPublished', 'getPublished'], false),
            $this->readBool($object, ['isPublished', 'getPublished'], false),
            $this->readBool($object, ['isArchived', 'getArchived'], false),
            $this->readBool($object, ['isDraft', 'getDraft'], false),
        );
    }

    public function apply(object $object, string $transition): ObjectVisibilityContext
    {
        $definition = $this->visibilityTransitions[$transition] ?? null;
        if (null === $definition) {
            throw new BadRequestHttpException(sprintf('Unsupported visibility transition "%s".', $transition));
        }

        $writes = $definition['writes'] ?? [];
        $this->writeFirst($object, $writes);
        $this->formHandler->flush($object);

        return $this->inspect($object);
    }

    /** @param array<string, bool> $writes */
    private function writeFirst(object $object, array $writes): void
    {
        $applied = false;
        foreach ($writes as $method => $value) {
            if (!method_exists($object, $method)) {
                continue;
            }

            $object->{$method}($value);
            $applied = true;
        }

        if (!$applied) {
            throw new BadRequestHttpException(sprintf('Object "%s" does not support visibility transitions.', $object::class));
        }
    }

    /** @param list<string> $candidates */
    private function readBool(object $object, array $candidates, bool $default): bool
    {
        foreach ($candidates as $candidate) {
            if (!method_exists($object, $candidate)) {
                continue;
            }

            return (bool) $object->{$candidate}();
        }

        return $default;
    }
}
