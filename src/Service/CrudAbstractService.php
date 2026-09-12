<?php

declare(strict_types=1);

namespace App\Cruding\Service;

use App\Cruding\DTO\Entrypoint\CrudServiceContextDTO;
use App\Cruding\DTO\Entrypoint\CrudServiceResultDTO;
use App\Cruding\ServiceInterface\Entrypoint\CrudDeleteServiceInterface;
use App\Cruding\ServiceInterface\Entrypoint\CrudGetServiceInterface;
use App\Cruding\ServiceInterface\Entrypoint\CrudGroundedServiceInterface;
use App\Cruding\ServiceInterface\Entrypoint\CrudPatchServiceInterface;
use App\Cruding\ServiceInterface\Entrypoint\CrudPostServiceInterface;
use App\Cruding\ServiceInterface\Entrypoint\CrudPutServiceInterface;
use App\Cruding\ServiceInterface\Entrypoint\CrudServiceBehaviorInterface;
use App\Cruding\ServiceInterface\Entrypoint\CrudServiceInterface;
use App\Cruding\ValueObject\Resource\CrudResourceContract;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Provides the abstract crud service responsibility within the Cruding component.
 */
abstract class CrudAbstractService implements CrudServiceInterface, CrudGroundedServiceInterface, CrudGetServiceInterface, CrudPostServiceInterface, CrudPutServiceInterface, CrudPatchServiceInterface, CrudDeleteServiceInterface
{
    private ?CrudServiceBehaviorInterface $defaultBehavior = null;

    #[Required]
    final public function setDefaultBehavior(
        #[Autowire(service: CrudDefaultServiceBehavior::class)]
        CrudServiceBehaviorInterface $defaultBehavior,
    ): void {
        $this->defaultBehavior = $defaultBehavior;
    }

    /**      * Indicates whether grounded.      */
    public function isGrounded(CrudServiceContextDTO $context): bool
    {
        return true;
    }

    /**      * Executes the get operation.      */
    public function get(CrudServiceContextDTO $context): CrudServiceResultDTO|Response|CrudResourceContract|null
    {
        return $this->executeDefault($context);
    }

    /**      * Executes the post operation.      */
    public function post(CrudServiceContextDTO $context): CrudServiceResultDTO|Response|CrudResourceContract|null
    {
        return $this->executeDefault($context);
    }

    /**      * Executes the put operation.      */
    public function put(CrudServiceContextDTO $context): CrudServiceResultDTO|Response|CrudResourceContract|null
    {
        return $this->executeDefault($context);
    }

    /**      * Executes the patch operation.      */
    public function patch(CrudServiceContextDTO $context): CrudServiceResultDTO|Response|CrudResourceContract|null
    {
        return $this->executeDefault($context);
    }

    /**      * Executes the delete operation.      */
    public function delete(CrudServiceContextDTO $context): CrudServiceResultDTO|Response|CrudResourceContract|null
    {
        return $this->executeDefault($context);
    }

    protected function beforeDefault(CrudServiceContextDTO $context): ?CrudServiceResultDTO
    {
        return null;
    }

    protected function afterDefault(CrudServiceContextDTO $context, CrudServiceResultDTO $result): CrudServiceResultDTO
    {
        return $result;
    }

    final protected function executeDefault(CrudServiceContextDTO $context): CrudServiceResultDTO
    {
        $before = $this->beforeDefault($context);
        if (null !== $before) {
            return $before;
        }

        if (null === $this->defaultBehavior) {
            return CrudServiceResultDTO::continueDefault(
                CrudServiceResultDTO::STATUS_DEFAULT_BEHAVIOR_UNAVAILABLE,
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
