<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud;

use App\Cruding\Dto\Crud\Entrypoint\CrudServiceContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudServiceResult;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudDeleteServiceInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudGetServiceInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudGroundedServiceInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPatchServiceInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPostServiceInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPutServiceInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudServiceBehaviorInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudServiceInterface;
use App\Cruding\Value\Resource\CrudResourceContract;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractCrudService implements CrudServiceInterface, CrudGroundedServiceInterface, CrudGetServiceInterface, CrudPostServiceInterface, CrudPutServiceInterface, CrudPatchServiceInterface, CrudDeleteServiceInterface
{
    private ?CrudServiceBehaviorInterface $defaultBehavior = null;

    #[Required]
    final public function setDefaultBehavior(
        #[Autowire(service: CrudDefaultServiceBehavior::class)]
        CrudServiceBehaviorInterface $defaultBehavior,
    ): void {
        $this->defaultBehavior = $defaultBehavior;
    }

    public function isGrounded(CrudServiceContext $context): bool
    {
        return true;
    }

    public function get(CrudServiceContext $context): CrudServiceResult|Response|CrudResourceContract|null
    {
        return $this->executeDefault($context);
    }

    public function post(CrudServiceContext $context): CrudServiceResult|Response|CrudResourceContract|null
    {
        return $this->executeDefault($context);
    }

    public function put(CrudServiceContext $context): CrudServiceResult|Response|CrudResourceContract|null
    {
        return $this->executeDefault($context);
    }

    public function patch(CrudServiceContext $context): CrudServiceResult|Response|CrudResourceContract|null
    {
        return $this->executeDefault($context);
    }

    public function delete(CrudServiceContext $context): CrudServiceResult|Response|CrudResourceContract|null
    {
        return $this->executeDefault($context);
    }

    protected function beforeDefault(CrudServiceContext $context): ?CrudServiceResult
    {
        return null;
    }

    protected function afterDefault(CrudServiceContext $context, CrudServiceResult $result): CrudServiceResult
    {
        return $result;
    }

    final protected function executeDefault(CrudServiceContext $context): CrudServiceResult
    {
        $before = $this->beforeDefault($context);
        if (null !== $before) {
            return $before;
        }

        if (null === $this->defaultBehavior) {
            return CrudServiceResult::continueDefault(
                CrudServiceResult::STATUS_DEFAULT_BEHAVIOR_UNAVAILABLE,
                [
                    'entrypoint' => static::class,
                    'resourcePath' => $context->resourcePath(),
                    'operation' => $context->operation(),
                ],
            );
        }

        return $this->afterDefault($context, $this->defaultBehavior->execute($context));
    }
}
