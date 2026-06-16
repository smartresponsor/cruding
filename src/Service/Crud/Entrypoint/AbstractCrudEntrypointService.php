<?php

declare(strict_types=1);

namespace App\Cruding\Service\Crud\Entrypoint;

use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointContext;
use App\Cruding\Dto\Crud\Entrypoint\CrudEntrypointResult;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudDeleteEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudEntrypointBehaviorInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudEntrypointServiceInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudGetEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudGroundedEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPatchEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPostEntrypointInterface;
use App\Cruding\ServiceInterface\Crud\Entrypoint\CrudPutEntrypointInterface;
use App\Cruding\Value\Surface\CrudSurfaceContract;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractCrudEntrypointService implements CrudEntrypointServiceInterface, CrudGroundedEntrypointInterface, CrudGetEntrypointInterface, CrudPostEntrypointInterface, CrudPutEntrypointInterface, CrudPatchEntrypointInterface, CrudDeleteEntrypointInterface
{
    private ?CrudEntrypointBehaviorInterface $defaultBehavior = null;

    #[Required]
    final public function setDefaultBehavior(
        #[Autowire(service: CrudDefaultEntrypointBehavior::class)]
        CrudEntrypointBehaviorInterface $defaultBehavior,
    ): void {
        $this->defaultBehavior = $defaultBehavior;
    }

    public function isGrounded(CrudEntrypointContext $context): bool
    {
        return true;
    }

    public function get(CrudEntrypointContext $context): CrudEntrypointResult|Response|CrudSurfaceContract|null
    {
        return $this->executeDefault($context);
    }

    public function post(CrudEntrypointContext $context): CrudEntrypointResult|Response|CrudSurfaceContract|null
    {
        return $this->executeDefault($context);
    }

    public function put(CrudEntrypointContext $context): CrudEntrypointResult|Response|CrudSurfaceContract|null
    {
        return $this->executeDefault($context);
    }

    public function patch(CrudEntrypointContext $context): CrudEntrypointResult|Response|CrudSurfaceContract|null
    {
        return $this->executeDefault($context);
    }

    public function delete(CrudEntrypointContext $context): CrudEntrypointResult|Response|CrudSurfaceContract|null
    {
        return $this->executeDefault($context);
    }

    protected function beforeDefault(CrudEntrypointContext $context): ?CrudEntrypointResult
    {
        return null;
    }

    protected function afterDefault(CrudEntrypointContext $context, CrudEntrypointResult $result): CrudEntrypointResult
    {
        return $result;
    }

    final protected function executeDefault(CrudEntrypointContext $context): CrudEntrypointResult
    {
        $before = $this->beforeDefault($context);
        if (null !== $before) {
            return $before;
        }

        if (null === $this->defaultBehavior) {
            return CrudEntrypointResult::continueDefault(
                CrudEntrypointResult::STATUS_DEFAULT_BEHAVIOR_UNAVAILABLE,
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
