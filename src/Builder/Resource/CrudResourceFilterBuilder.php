<?php

declare(strict_types=1);

namespace App\Cruding\Builder\Resource;

use App\Cruding\Service\Resource\CrudResourceLabelFormatter;
use App\Tabling\Service\TableFilterMetadataBuilder;

/**
 * Builds resource filter builder values used by Cruding workflows.
 */
final class CrudResourceFilterBuilder
{
    public function __construct(
        private readonly CrudResourceLabelFormatter $labelFormatter,
        private readonly TableFilterMetadataBuilder $tableFilterMetadataBuilder,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function build(string $resourcePath): array
    {
        return $this->tableFilterMetadataBuilder->build($this->labelFormatter->humanize($resourcePath));
    }
}
