<?php

declare(strict_types=1);

namespace App\Cruding\ServiceInterface\Crud;

use App\Cruding\Dto\Capability\CrudCapabilityMatch;
use App\Cruding\Dto\Capability\CrudCapabilityProfile;
use App\Cruding\Dto\Crud\CrudContext;

interface CrudCapabilityResolverInterface
{
    /**
     * @return array{supportsSlug: bool, supportsId: bool}
     */
    public function resolve(CrudContext $context, ?object $object = null): array;

    public function profile(object|string $subject): CrudCapabilityProfile;

    public function match(string $capability, object|string $subject): CrudCapabilityMatch;

    public function supports(string $capability, object|string $subject): bool;
}
