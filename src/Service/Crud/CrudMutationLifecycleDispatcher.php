<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\CrudMutationLifecycleContext;
use App\Cruding\ServiceInterface\Crud\CrudMutationLifecycleSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final readonly class CrudMutationLifecycleDispatcher
{
    /** @param iterable<CrudMutationLifecycleSubscriberInterface> $subscriberList */
    public function __construct(
        private iterable $subscriberList,
        private ManagerRegistry $managerRegistry,
    ) {
    }

    public function execute(CrudMutationLifecycleContext $context, callable $mutation): mixed
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

    public function before(CrudMutationLifecycleContext $context): void
    {
        foreach ($this->subscriberList as $subscriber) {
            if ($subscriber->supports($context)) {
                $subscriber->before($context);
            }
        }
    }

    public function after(CrudMutationLifecycleContext $context): void
    {
        foreach ($this->subscriberList as $subscriber) {
            if ($subscriber->supports($context)) {
                $subscriber->after($context);
            }
        }
    }
}
