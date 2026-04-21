<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Capability\CapabilityMatch;
use App\Cruding\Dto\Capability\CapabilityProfile;
use App\Cruding\Dto\Crud\CrudContext;

interface CrudCapabilityResolverInterface
{
    /**
     * @return array{supportsSlug: bool, supportsId: bool}
     */
    public function resolve(CrudContext $context, ?object $object = null): array;

    public function profile(object|string $subject): CapabilityProfile;

    public function match(string $capability, object|string $subject): CapabilityMatch;

    public function supports(string $capability, object|string $subject): bool;
}
