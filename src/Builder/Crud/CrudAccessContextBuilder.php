<?php

declare(strict_types=1);

namespace App\Cruding\Builder\Crud;

use App\Cruding\Dto\Crud\CrudAccessContext;
use App\Cruding\Dto\Crud\CrudContext;
use App\Cruding\ServiceInterface\Crud\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\Crud\CrudCapabilityResolverInterface;
use App\Cruding\ServiceInterface\Crud\CrudOwnershipResolverInterface;

final readonly class CrudAccessContextBuilder implements CrudAccessContextBuilderInterface
{
    public function __construct(
        private CrudCapabilityResolverInterface $capabilityResolver,
        private CrudOwnershipResolverInterface $ownershipResolver,
    ) {
    }

    public function build(CrudContext $context, ?object $object = null): CrudAccessContext
    {
        $capabilities = $this->capabilityResolver->resolve($context, $object);
        $ownership = $this->ownershipResolver->resolve($object);
        $isAdminSurface = $context->isAdminSurface();

        $canView = $isAdminSurface ? $ownership->isAdmin : true;
        $canEdit = $isAdminSurface ? $ownership->isAdmin : ($ownership->isAdmin || $ownership->canMutate());
        $canDelete = $isAdminSurface ? $ownership->isAdmin : ($ownership->isAdmin || $ownership->canMutate());

        return new CrudAccessContext(
            $context,
            (bool) ($capabilities['supportsSlug'] ?? false),
            (bool) ($capabilities['supportsId'] ?? true),
            $ownership,
            $canView,
            $canEdit,
            $canDelete,
        );
    }
}
