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
        $isAdminView = $context->isAdminView();

        $canView = $isAdminView ? $ownership->isAdmin : true;
        $canEdit = $isAdminView ? $ownership->isAdmin : ($ownership->isAdmin || $ownership->canMutate());
        $canDelete = $isAdminView ? $ownership->isAdmin : ($ownership->isAdmin || $ownership->canMutate());

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
