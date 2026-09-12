<?php

declare(strict_types=1);

namespace App\Cruding\Service;

use App\Cruding\DTO\CrudMutationLifecycleContextDTO;
use App\Cruding\ServiceInterface\CrudMutationLifecycleSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Provides the mutation lifecycle dispatcher responsibility within the Cruding component.
 */
final readonly class CrudMutationLifecycleDispatcher
{
    /** @param iterable<CrudMutationLifecycleSubscriberInterface> $subscriberList */
    public function __construct(
        private iterable $subscriberList,
        private ManagerRegistry $managerRegistry,
    ) {
    }

    /**      * Executes the operation represented by this service.      */
    public function execute(CrudMutationLifecycleContextDTO $context, callable $mutation): mixed
    {
        $manager = $this->managerRegistry->getManagerForClass($context->object::class) ?? $this->managerRegistry->getManager();
        $operation = function () use ($context, $mutation): mixed {
            $this->before($context);
            $result = $mutation();
            $this->after($context);

            return $result;
        };

        return $manager instanceof EntityManagerInterface
            ? $manager->wrapInTransaction($operation)
            : $operation();
    }

    /**      * Executes the before operation.      */
    public function before(CrudMutationLifecycleContextDTO $context): void
    {
        foreach ($this->subscriberList as $subscriber) {
            if ($subscriber->supports($context)) {
                $subscriber->before($context);
            }
        }
    }

    /**      * Executes the after operation.      */
    public function after(CrudMutationLifecycleContextDTO $context): void
    {
        foreach ($this->subscriberList as $subscriber) {
            if ($subscriber->supports($context)) {
                $subscriber->after($context);
            }
        }
    }
}
