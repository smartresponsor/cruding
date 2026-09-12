<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface;

use App\Cruding\DTO\Capability\CrudCapabilityMatchDTO;
use App\Cruding\DTO\Capability\CrudCapabilityProfileDTO;
use App\Cruding\DTO\CrudContextDTO;

/**
 * Defines the contract for capability resolver within the Cruding component.
 */
interface CrudCapabilityResolverInterface
{
    /**
     * @return array{supportsSlug: bool, supportsId: bool}
     */
    public function resolve(CrudContextDTO $context, ?object $object = null): array;

    /**      * Executes the profile operation.      */
    public function profile(object|string $subject): CrudCapabilityProfileDTO;

    /**      * Executes the match operation.      */
    public function match(string $capability, object|string $subject): CrudCapabilityMatchDTO;

    /**      * Indicates whether this implementation supports the supplied context.      */
    public function supports(string $capability, object|string $subject): bool;
}
