<?php

declare(strict_types=1);

namespace App\Cruding\Builder;

use App\Cruding\DTO\CrudAccessContextDTO;
use App\Cruding\DTO\CrudContextDTO;
use App\Cruding\ServiceInterface\CrudAccessContextBuilderInterface;
use App\Cruding\ServiceInterface\CrudCapabilityResolverInterface;
use App\Cruding\ServiceInterface\CrudOwnershipResolverInterface;

/**
 * Builds access context builder values used by Cruding workflows.
 */
final readonly class CrudAccessContextBuilder implements CrudAccessContextBuilderInterface
{
    public function __construct(
        private CrudCapabilityResolverInterface $capabilityResolver,
        private CrudOwnershipResolverInterface $ownershipResolver,
    ) {
    }

    /**      * Executes the build operation.      */
    public function build(CrudContextDTO $context, ?object $object = null): CrudAccessContextDTO
    {
        $capabilities = $this->capabilityResolver->resolve($context, $object);
        $ownership = $this->ownershipResolver->resolve($object);
        $isAdminView = $context->isAdminView();

        $canView = $isAdminView ? $ownership->isAdmin : true;
        $canEdit = $isAdminView ? $ownership->isAdmin : ($ownership->isAdmin || $ownership->canMutate());
        $canDelete = $isAdminView ? $ownership->isAdmin : ($ownership->isAdmin || $ownership->canMutate());

        return new CrudAccessContextDTO(
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
